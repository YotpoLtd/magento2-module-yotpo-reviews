<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;
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
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

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
     * @param  YotpoHelper            $yotpoHelper
     * @param  OrderCollectionFactory $orderCollectionFactory
     * @param  YotpoSyncFactory       $yotpoSyncFactory
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        OrderCollectionFactory $orderCollectionFactory,
        YotpoSyncFactory $yotpoSyncFactory,
        array $data = []
    ) {
        $this->_yotpoHelper = $yotpoHelper;
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
            return $this->_yotpoHelper->getCurrentStoreId();
        }
    }

    protected function getStoreIds()
    {
        if (($_storeId = $this->getRequest()->getParam("store", 0))) {
            $stores = [$_storeId];
        } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
            $stores = $this->_yotpoHelper->getStoreManager()->getWebsite($websiteId)->getStoreIds();
        } else {
            $stores = [];
            foreach ($this->_yotpoHelper->getAllStoreIds(true) as $storeId) {
                $stores[] = $storeId;
            }
        }
        return array_values($stores);
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
        $status = [];

        $status['total_orders'] = $this->getOrderCollection()
            ->addAttributeToFilter('main_table.status', $this->_yotpoHelper->getCustomOrderStatus())
            ->addAttributeToFilter('main_table.store_id', ['in' => $this->getStoreIds()])
            ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->_yotpoHelper->getOrdersSyncAfterDate()])
            ->getSize();

        $status['total_orders_synced'] = $this->getOrderCollection()
            ->addAttributeToFilter('main_table.status', $this->_yotpoHelper->getCustomOrderStatus())
            ->addAttributeToFilter('main_table.store_id', ['in' => $this->getStoreIds()])
            ->addAttributeToFilter('main_table.created_at', ['gteq' => $this->_yotpoHelper->getOrdersSyncAfterDate()])
            ->addAttributeToFilter('yotpo_sync.sync_flag', 1)
            ->getSize();

        $lastSyncDate = $this->_yotpoSyncFactory->create()->getCollection()
            ->addFieldToFilter('entity_type', 'orders')
            ->setOrder('sync_flag', 'DESC')
            ->setPageSize(1)
            ->getFirstItem();

        $status['last_sync_date'] = ($lastSyncDate && $lastSyncDate->getSyncDate()) ? $lastSyncDate->getSyncDate() : "never";

        return $status;
    }
}
