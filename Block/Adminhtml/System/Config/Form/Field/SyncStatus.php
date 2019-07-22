<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\SyncFactory as YotpoSyncFactory;

class SyncStatus extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::system/config/sync_status.phtml';

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var YotpoSyncFactory
     */
    protected $_yotpoSyncFactory;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  YotpoConfig            $yotpoConfig
     * @param  OrderCollectionFactory $orderCollectionFactory
     * @param  YotpoSyncFactory       $yotpoSyncFactory
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        OrderCollectionFactory $orderCollectionFactory,
        YotpoSyncFactory $yotpoSyncFactory,
        array $data = []
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_yotpoSyncFactory = $yotpoSyncFactory;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Yotpo_Yotpo::system/config/sync_status.phtml');
        }
        return $this;
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    protected function getStoreId()
    {
        if ($this->getRequest()->getParam('store', 0)) {
            return $this->getRequest()->getParam('store', 0);
        } else {
            return $this->yotpoConfig->getCurrentStoreId();
        }
    }

    protected function getStoreIds()
    {
        if (!$this->hasData('storeIds')) {
            if (($_storeId = $this->getRequest()->getParam("store", 0))) {
                $stores = [$_storeId];
            } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
                $stores = $this->yotpoConfig->getStoreManager()->getWebsite($websiteId)->getStoreIds();
            } else {
                $stores = $this->yotpoConfig->getAllStoreIds(false);
            }
            $this->setData('storeIds', $this->yotpoConfig->filterDisabledStoreIds($stores));
        }
        return $this->getData('storeIds');
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

    public function getStatus()
    {
        $status = [
            'total_orders' => 0,
            'total_orders_synced' => 0,
            'total_orders_synced_all' => 0,
            'last_sync_date' => "never",
        ];

        foreach ($this->getStoreIds() as $key => $storeId) {
            $status['total_orders'] += $this->getOrderCollection()
                ->addAttributeToFilter('main_table.status', ['in' => $this->yotpoConfig->getCustomOrderStatus($storeId, ScopeInterface::SCOPE_STORE)])
                ->addAttributeToFilter('main_table.store_id', $storeId)
                ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->yotpoConfig->getOrdersSyncAfterDate($storeId, ScopeInterface::SCOPE_STORE)])
                ->getSize();
            $status['total_orders_synced'] += $this->getOrderCollection()
                ->addAttributeToFilter('main_table.status', ['in' => $this->yotpoConfig->getCustomOrderStatus($storeId, ScopeInterface::SCOPE_STORE)])
                ->addAttributeToFilter('main_table.store_id', $storeId)
                ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->yotpoConfig->getOrdersSyncAfterDate($storeId, ScopeInterface::SCOPE_STORE)])
                ->addAttributeToFilter('yotpo_sync.sync_flag', 1)
                ->getSize();
            $status['total_orders_synced_all'] += $this->getOrderCollection()
                ->addAttributeToFilter('main_table.store_id', $storeId)
                ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->yotpoConfig->getOrdersSyncAfterDate($storeId, ScopeInterface::SCOPE_STORE)])
                ->addAttributeToFilter('yotpo_sync.sync_flag', 1)
                ->getSize();
        }

        $lastSyncDate = $this->_yotpoSyncFactory->create()->getCollection()
            ->addFieldToFilter('entity_type', 'orders')
            ->addFieldToFilter('store_id', ['in' => $this->getStoreIds()])
            ->setOrder('sync_flag', 'DESC')
            ->setPageSize(1)
            ->getFirstItem();

        $status['last_sync_date'] = ($lastSyncDate && $lastSyncDate->getSyncDate()) ? $lastSyncDate->getSyncDate() : "never";

        return $status;
    }
}
