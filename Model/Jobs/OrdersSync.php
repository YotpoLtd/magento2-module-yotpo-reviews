<?php

namespace Yotpo\Yotpo\Model\Jobs;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Yotpo\Yotpo\Model\AbstractJobs;
use Yotpo\Yotpo\Model\Api\Purchases as YotpoApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\Schema as YotpoSchema;

class OrdersSync extends AbstractJobs
{
    /**
     * Orders Sync Limit:
     */
    private $limit = null;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * @var YotpoSchema
     */
    private $yotpoSchema;

    /**
     * @method __construct
     * @param  NotifierInterface      $notifierPool
     * @param  AppState               $appState
     * @param  YotpoConfig            $yotpoConfig
     * @param  ResourceConnection     $resourceConnection
     * @param  AppEmulation           $appEmulation
     * @param  OrderCollectionFactory $orderCollectionFactory
     * @param  YotpoApi               $yotpoApi
     * @param  YotpoSchema            $yotpoSchema
     */
    public function __construct(
        NotifierInterface $notifierPool,
        AppState $appState,
        YotpoConfig $yotpoConfig,
        ResourceConnection $resourceConnection,
        AppEmulation $appEmulation,
        OrderCollectionFactory $orderCollectionFactory,
        YotpoApi $yotpoApi,
        YotpoSchema $yotpoSchema
    ) {
        parent::__construct($notifierPool, $appState, $yotpoConfig, $resourceConnection, $appEmulation);
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->yotpoApi = $yotpoApi;
        $this->yotpoSchema = $yotpoSchema;
    }

    private function flagItems($entityType, $storeId, array $entityIds)
    {
        foreach ($entityIds as &$entityId) {
            $entityId = [
                "store_id" => $storeId,
                "entity_type" => $entityType,
                "entity_id" => $entityId,
                "sync_flag" => 1,
                "sync_date" => $this->_yotpoConfig->getCurrentDate(),
            ];
        }
        return $this->_resourceConnection->getConnection('sales')
            ->insertOnDuplicate($this->_resourceConnection->getTableName('yotpo_sync', 'sales'), $entityIds, ['store_id', 'entity_type', 'entity_id', 'sync_flag', 'sync_date']);
    }

    /**
     * @method getOrderCollection
     * @return OrderCollection
     * @api
     */
    public function getOrderCollection()
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['yotpo_sync'=>$collection->getTable('yotpo_sync')],
            "main_table.entity_id = yotpo_sync.entity_id AND main_table.store_id = yotpo_sync.store_id AND yotpo_sync.entity_type = 'orders'",
            [
                'yotpo_sync_flag'=>'yotpo_sync.sync_flag'
            ]
        );
        return $collection;
    }

    /**
     * @method addOrderCollectionFilters
     * @param  OrderCollection           $collection
     * @param  int|null                  $storeId
     * @return OrderCollection
     * @api
     */
    public function addOrderCollectionFilters(OrderCollection $collection, $storeId = null)
    {
        $storeId = is_null($storeId) ? $this->_yotpoConfig->getCurrentStoreId() : $storeId;
        return $collection
            ->addAttributeToFilter('main_table.status', ['in' => $this->_yotpoConfig->getCustomOrderStatus()])
            ->addAttributeToFilter('main_table.store_id', $storeId)
            ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->_yotpoConfig->getOrdersSyncAfterDate()])
            ->addAttributeToFilter('yotpo_sync.sync_flag', [['null' => true],['eq' => 0]])
            ->addAttributeToSort('main_table.created_at', 'ASC');
    }

    /**
     * @method setOrderCollectionLimit
     * @param  OrderCollection         $collection
     * @return OrderCollection
     * @api
     */
    public function setOrderCollectionLimit(OrderCollection $collection, $limit = null)
    {
        if (!is_null($limit)) {
            $collection->setPageSize($limit);
        } elseif (($limit = ($this->limit === null) ? $this->_yotpoConfig->getOrdersSyncLimit() : $this->limit)) {
            $collection->setPageSize($limit);
        }
        return $collection;
    }

    /**
     * @method setLimit
     * @param null|int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @method getLimit
     * @return null|int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @method getCollectionIds
     * @param  $collection
     * @return array
     */
    private function getCollectionIds($collection)
    {
        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getId();
        }
        return $ids;
    }

    public function execute()
    {
        try {
            foreach ($this->_yotpoConfig->getAllStoreIds(false) as $storeId) {
                try {
                    $this->emulateFrontendArea($storeId);
                    if (!$this->_yotpoConfig->isEnabled()) {
                        $this->_processOutput("OrdersSync::execute() - Skipping store ID: {$storeId} (disabled)", "debug");
                        continue;
                    }
                    $this->_processOutput("OrdersSync::execute() - Processing orders for store ID: {$storeId} ...", "debug");

                    $ordersCollection = $this->getOrderCollection();
                    $ordersCollection = $this->addOrderCollectionFilters($ordersCollection, $storeId);
                    $ordersCollection = $this->setOrderCollectionLimit($ordersCollection);

                    $orders = $this->yotpoSchema->prepareOrdersData($ordersCollection);
                    $ordersCollectionCount = $ordersCollection->count();
                    $ordersCount = count($orders);
                    $this->_processOutput("OrdersSync::execute() - Found {$ordersCount} orders for sync (" . ($ordersCollectionCount - $ordersCount) . " skipped).", "debug");
                    if ($ordersCount > 0) {
                        $resData = $this->yotpoApi->massCreate($orders, $storeId);
                        $status = (int) ((is_object($resData['body']) && property_exists($resData['body'], "code")) ? $resData['body']->code : $resData['status']);
                        if (!$status || 500 <= $status) {
                            $this->_processOutput("OrdersSync::execute() - Orders sync for store ID: {$storeId} [FAILURE]", "error", $resData);
                        } else {
                            $this->flagItems('orders', $storeId, $this->getCollectionIds($ordersCollection));
                            if ($status !== 200) {
                                $this->_processOutput("OrdersSync::execute() - Orders sync for store ID: {$storeId} [WARNING] Some of the orders failed to sync due to Yotpo API's internal validation.", "error", $resData);
                            } else {
                                $this->_processOutput("OrdersSync::execute() - Orders sync for store ID: {$storeId} [SUCCESS]", "debug");
                            }
                        }
                    } elseif ($ordersCollectionCount) {
                        $this->flagItems('orders', $storeId, $this->getCollectionIds($ordersCollection));
                    }
                } catch (\Exception $e) {
                    $this->_processOutput("OrdersSync::execute() - Exception: Failed sync orders for store ID: {$storeId} - " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                }
                $this->stopEnvironmentEmulation();
            }
        } catch (\Exception $e) {
            $this->_processOutput("OrdersSync::execute() - Exception: Failed to sync orders. " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
