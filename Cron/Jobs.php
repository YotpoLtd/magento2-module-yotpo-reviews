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
    private $_memoryLimitUpdated = false;
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
                call_user_func([$this, $method], $val);
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
            $this->_output->writeln('<' . $type . '>' . print_r($message, true) . '</' . $type . '>');
            if ($data) {
                $this->_output->writeln('<comment>' . print_r($data, true) . '</comment>');
            }
        } else {
            //Add admin error notification
            if ($type === 'error' && !$this->_adminNotificationError) {
                $this->addAdminNotification("Yopto - An error occurred during the automated sync process!", "*If you enabled debug mode on Yotpo - Reviews & Visual Marketing, you should see more details in the log file (var/log/system.log)", 'critical');
                $this->_adminNotificationError = true;
            }
        }

        //Log to var/log/system.log
        $this->_yotpoHelper->log($message, $type, $data);

        return $this;
    }

    protected function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        call_user_func_array([$this->_notifierPool, 'add' . ucfirst($type)], [$title, $description]);
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

    public function resetSyncFlags($entityType = null)
    {
        try {
            if ($this->_yotpoHelper->isEnabled()) {
                $this->_processOutput("Yotpo - resetSyncFlags (entity: {$entityType}) [STARTED]", "info");
                $this->updateMemoryLimit();
                $this->setCrontabAreaCode();
                $this->_resourceConnection->getConnection()->query(
                    "UPDATE `yotpo_sync` SET `sync_flag`='0'" .
                    (($entityType) ? " WHERE `entity_type`='{$entityType}'" : "")
                );
                $this->_processOutput("Yotpo - resetSyncFlags (entity: {$entityType}) [DONE]", "info");
            }
        } catch (\Exception $e) {
            $this->_processOutput("Yotpo Exception on resetSyncFlags:  " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
    }

    public function ordersSync()
    {
        try {
            if ($this->_yotpoHelper->isEnabled()) {
                $this->_processOutput("Yotpo - ordersSync [STARTED]", "info");
                $this->updateMemoryLimit();
                $this->setCrontabAreaCode();

                foreach ($this->_yotpoHelper->getAllStoreIds(true) as $storeId) {
                    try {
                        $this->_processOutput("Yotpo - Processing orders for store ID: {$storeId} ...", "info");
                        $this->_yotpoHelper->emulateFrontendArea($storeId, true);

                        if (!(($appKey = $this->_yotpoHelper->getAppKey()) && ($secret = $this->_yotpoHelper->getSecret()))) {
                            $this->_processOutput(__('Please make sure you insert your APP KEY and SECRET and save configuration before trying to export past orders'), "error");
                            continue;
                        }
                        if (!($token = $this->_yotpoApi->oauthAuthentication())) {
                            $this->_processOutput(__("Please make sure the APP KEY and SECRET you've entered are correct"), "error");
                            continue;
                        }

                        $ordersCollection = $this->getOrderCollection()
                            ->addAttributeToFilter('main_table.status', $this->_yotpoHelper->getCustomOrderStatus())
                            ->addAttributeToFilter('main_table.store_id', $storeId)
                            ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->_yotpoHelper->getOrdersSyncAfterDate()])
                            ->addAttributeToFilter('yotpo_sync.sync_flag', [['null' => true],['eq' => 0]])
                            ->addAttributeToSort('main_table.created_at', 'ASC');
                        if (($limit = (is_null($this->_limit)) ? $this->_yotpoHelper->getOrdersSyncLimit() : $this->_limit)) {
                            $ordersCollection->setPageSize($limit);
                        }

                        $orders = $this->_yotpoApi->prepareOrdersData($ordersCollection);
                        $ordersCount = count($orders);
                        $this->_processOutput("Yotpo - Found {$ordersCount} orders for sync.", "info");
                        if ($ordersCount > 0) {
                            $resData = $this->_yotpoApi->massCreatePurchases($orders, $token);
                            if ($resData['status'] != 200) {
                                $this->_processOutput("Yotpo - Orders sync for store ID: {$storeId} [FAILURE]", "error", $resData);
                            } else {
                                $this->flagItems('orders', $storeId, $ordersCollection->getAllIds());
                                $this->_processOutput("Yotpo - Orders sync for store ID: {$storeId} [SUCCESS]", "info");
                            }
                        }
                    } catch (\Exception $e) {
                        $this->_processOutput("Yotpo Exception - Failed sync orders for store ID: {$storeId} - " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
                    }
                    $this->_yotpoHelper->stopEnvironmentEmulation();
                }

                $this->_processOutput("Yotpo - ordersSync [DONE]", "info");
            }
        } catch (\Exception $e) {
            $this->_processOutput("Yotpo Exception - Failed to sync orders. " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
    }

    /////////////
    // Helpers //
    /////////////

    /**
     * @return void
     */
    private function updateMemoryLimit()
    {
        if (!$this->_memoryLimitUpdated && function_exists('\ini_set')) {
            @ini_set('display_errors', 1);
            @ini_set('memory_limit', '2048M');
            $this->_memoryLimitUpdated = true;
        }
    }

    private function setCrontabAreaCode()
    {
        try {
            $this->_appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        } catch (\Exception $e) {
        }
        return $this;
    }
}
