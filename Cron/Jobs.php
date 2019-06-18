<?php

namespace Yotpo\Yotpo\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Jobs
{
    private $_adminNotificationError = false;

    /**
     * System config (defaults):
     */
    protected $_limit = null;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @var AppState
     */
    protected $_appState;

    /**
     * @var NotifierInterface
     */
    protected $_notifierPool;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var OutputInterface
     */
    protected $_output;

    /**
     * @method __construct
     * @param  YotpoHelper            $yotpoHelper
     * @param  YotpoApiClient         $yotpoApi
     * @param  AppState				  $appState
     * @param  NotifierInterface      $notifierPool
     * @param  OrderCollectionFactory $orderCollectionFactory
     * @param  ResourceConnection 	  $resourceConnection
     */
    public function __construct(
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi,
        AppState $appState,
        NotifierInterface $notifierPool,
        OrderCollectionFactory $orderCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
        $this->_appState = $appState;
        $this->_notifierPool = $notifierPool;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_logger = $yotpoHelper->getLogger();
    }

    /**
     * @method initConfig
     * @param array $config
     * @return $this
     */
    public function initConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $method = $this->_yotpoHelper->strToCamelCase(strtolower($key), 'set');
            if (method_exists($this, $method)) {
                $this->{$method}($val);
            }
        }
        return $this;
    }

    /**
     * @method setOutput
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->_output = $output;
        return $this;
    }

    /**
     * @method getOutput
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * @method setLimit
     * @param null|int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @method getLimit
     * @return null|int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * Process output messages (log to system.log / output to terminal)
     * @method _processOutput
     * @return $this
     */
    protected function _processOutput($message, $type = "info", $data = [])
    {
        if ($this->_output instanceof OutputInterface) {
            //Output to terminal
            $this->_output->writeln('<' . $type . '>' . json_encode($message) . '</' . $type . '>');
            if ($data) {
                $this->_output->writeln('<comment>' . json_encode($data) . '</comment>');
            }
        } else {
            //Add admin error notification
            if ($type === 'error' && !$this->_adminNotificationError) {
                $this->addAdminNotification("Yopto - An error occurred during the automated sync process! (module: Yotpo_Yotpo)", "*If you enabled debug mode on Yotpo - Reviews & Visual Marketing, you should see more details in the log file (var/log/system.log)", 'critical');
                $this->_adminNotificationError = true;
            }
        }

        //Log to var/log/system.log
        $this->_yotpoHelper->log($message, $type, $data);

        return $this;
    }

    protected function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        $method = 'add' . ucfirst($type);
        $this->_notifierPool->{$method}($title, $description);
        return $this;
    }

    protected function flagItems($entityType, $storeId, array $entityIds)
    {
        foreach ($entityIds as &$entityId) {
            $entityId = [
                "store_id" => $storeId,
                "entity_type" => $entityType,
                "entity_id" => $entityId,
                "sync_flag" => 1,
                "sync_date" => $this->_yotpoHelper->getCurrentDate(),
            ];
        }
        return $this->_resourceConnection->getConnection()->insertOnDuplicate('yotpo_sync', $entityIds, ['store_id', 'entity_type', 'entity_id', 'sync_flag', 'sync_date']);
    }

    protected function getOrderCollection()
    {
        $collection = $this->_orderCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['yotpo_sync'=>$collection->getTable('yotpo_sync')],
            "main_table.entity_id = yotpo_sync.entity_id AND main_table.store_id = yotpo_sync.store_id AND yotpo_sync.entity_type = 'orders'",
            [
                'yotpo_sync_flag'=>'yotpo_sync.sync_flag'
            ]
        );
        return $collection;
    }

    //========================================================================//

    public function updateMetadata()
    {
        try {
            if ($this->_yotpoHelper->isEnabled()) {
                $this->_processOutput("Jobs::updateMetadata() - [STARTED]", "info");
                $this->setCrontabAreaCode();
                foreach ($this->_yotpoHelper->getAllStoreIds(false) as $storeId) {
                    try {
                        $this->_yotpoHelper->emulateFrontendArea($storeId, true);
                        if (!$this->_yotpoHelper->isEnabled()) {
                            $this->_processOutput("Jobs::updateMetadata() - Skipping store ID: {$storeId} (disabled)", "info");
                            continue;
                        }
                        $this->_processOutput("Jobs::updateMetadata() - Updating metadata for store ID: {$storeId} ...", "info");
                        $result = $this->_yotpoApi->updateMetadata($storeId);
                        $this->_processOutput("Jobs::updateMetadata() - Updating metadata for store ID: {$storeId} [SUCCESS]", "info");
                    } catch (\Exception $e) {
                        $this->_processOutput("Jobs::updateMetadata() - Exception on store ID: {$storeId} - " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                    }
                    $this->_yotpoHelper->stopEnvironmentEmulation();
                }
                $this->_processOutput("Jobs::updateMetadata() - [DONE]", "info");
            }
        } catch (\Exception $e) {
            $this->_processOutput("Jobs::updateMetadata() - Exception:  " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }

    public function resetSyncFlags($entityType = null)
    {
        try {
            $this->_processOutput("Jobs::resetSyncFlags() - (entity: {$entityType}) [STARTED]", "info");
            $this->setCrontabAreaCode();
            $this->_resourceConnection->getConnection()->update(
                $this->_resourceConnection->getTableName('yotpo_sync'),
                ['sync_flag' => 0],
                (($entityType) ? ['entity_type = ?' => "{$entityType}"] : [])
                );
            $this->_processOutput("Yotpo - resetSyncFlags (entity: {$entityType}) [DONE]", "info");
        } catch (\Exception $e) {
            $this->_processOutput("Jobs::resetSyncFlags() - Exception:  " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }

    public function ordersSync()
    {
        try {
            $this->_processOutput("Jobs::ordersSync() - [STARTED]", "info");
            $this->setCrontabAreaCode();

            foreach ($this->_yotpoHelper->getAllStoreIds(false) as $storeId) {
                try {
                    $this->_yotpoHelper->emulateFrontendArea($storeId, true);
                    if (!$this->_yotpoHelper->isEnabled()) {
                        $this->_processOutput("Jobs::ordersSync() - Skipping store ID: {$storeId} (disabled)", "info");
                        continue;
                    }
                    $this->_processOutput("Jobs::ordersSync() - Processing orders for store ID: {$storeId} ...", "info");
                    if (!(($appKey = $this->_yotpoHelper->getAppKey()) && ($secret = $this->_yotpoHelper->getSecret()))) {
                        $this->_processOutput(__("Jobs::ordersSync() - Error: Please make sure the APP KEY and SECRET you've entered are correct"), "error");
                        continue;
                    }
                    if (!($token = $this->_yotpoApi->oauthAuthentication())) {
                        $this->_processOutput(__("Jobs::ordersSync() - Error: Please make sure the APP KEY and SECRET you've entered are correct"), "error");
                        continue;
                    }

                    $ordersCollection = $this->getOrderCollection()
                            ->addAttributeToFilter('main_table.status', $this->_yotpoHelper->getCustomOrderStatus())
                            ->addAttributeToFilter('main_table.store_id', $storeId)
                            ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->_yotpoHelper->getOrdersSyncAfterDate()])
                            ->addAttributeToFilter('yotpo_sync.sync_flag', [['null' => true],['eq' => 0]])
                            ->addAttributeToSort('main_table.created_at', 'ASC');
                    if (($limit = ($this->_limit === null) ? $this->_yotpoHelper->getOrdersSyncLimit() : $this->_limit)) {
                        $ordersCollection->setPageSize($limit);
                    }

                    $orders = $this->_yotpoApi->prepareOrdersData($ordersCollection);
                    $ordersCount = count($orders);
                    $this->_processOutput("Jobs::ordersSync() - Found {$ordersCount} orders for sync.", "info");
                    if ($ordersCount > 0) {
                        $resData = $this->_yotpoApi->massCreatePurchases($orders, $token);
                        $status = (is_object($resData['body']) && property_exists($resData['body'], "code")) ? $resData['body']->code : $resData['status'];
                        if ($status != 200) {
                            $this->_processOutput("Jobs::ordersSync() - Orders sync for store ID: {$storeId} [FAILURE]", "error", $resData);
                        } else {
                            $this->flagItems('orders', $storeId, $ordersCollection->getAllIds());
                            $this->_processOutput("Jobs::ordersSync() - Orders sync for store ID: {$storeId} [SUCCESS]", "info");
                        }
                    }
                } catch (\Exception $e) {
                    $this->_processOutput("Jobs::ordersSync() - Exception: Failed sync orders for store ID: {$storeId} - " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                }
                $this->_yotpoHelper->stopEnvironmentEmulation();
            }

            $this->_processOutput("Jobs::ordersSync() - [DONE]", "info");
        } catch (\Exception $e) {
            $this->_processOutput("Jobs::ordersSync() - Exception: Failed to sync orders. " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }

    /////////////
    // Helpers //
    /////////////

    private function setCrontabAreaCode()
    {
        try {
            $this->_appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        } catch (\Exception $e) {
        }
        return $this;
    }
}
