<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class MassMap extends \Magento\Backend\App\Action
{
    //max amount of orders to export
    const MAX_ORDERS_TO_EXPORT = 5000;
    const MAX_BULK_SIZE        = 200;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @var OrderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  YotpoHelper            $yotpoHelper
     * @param  YotpoApiClient         $yotpoApi
     * @param  OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $success = true;
            $storeId = $this->getRequest()->getParam("store_id");
            $appKey = $this->_yotpoHelper->getAppKey($storeId);
            $secret = $this->_yotpoHelper->getSecret($storeId);
            if (!($secret && $appKey)) {
                $this->_messageManager->addError(__('Please make sure you insert your APP KEY and SECRET and save configuration before trying to export past orders'));
                return;
            }
            $token = $this->_yotpoApi->oauthAuthentication($storeId);
            if ($token == null) {
                $this->_messageManager->addError(__("Please make sure the APP KEY and SECRET you've entered are correct"));
                return;
            }

            $ordersCollection = $this->_orderCollectionFactory->create()
                ->addAttributeToFilter('status', $this->_yotpoHelper->getCustomOrderStatus($storeId))
                ->addAttributeToFilter('created_at', ['gteq' => $this->_yotpoHelper->getTimeFrame()])
                ->addAttributeToSort('created_at', 'DESC')
                ->setPageSize(self::MAX_BULK_SIZE);
            if ($storeId) {
                $ordersCollection->addAttributeToFilter('store_id', $storeId);
            }

            $pages = $ordersCollection->getLastPageNumber();
            $success = true;
            $offset = 0;
            do {
                try {
                    $offset++;
                    $ordersCollection->setCurPage($offset)->load();
                    $orders = [];
                    foreach ($ordersCollection as $order) {
                        $orders[] = $this->_yotpoApi->prepareOrderData($order);
                    }
                    if (count($orders) > 0) {
                        $resData = $this->_yotpoApi->massCreatePurchases($orders, $token, $storeId);
                        $success = ($resData['status'] != 200) ? false : $success;
                    }
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
                }
                $ordersCollection->clear();
            } while ($offset <= (self::MAX_ORDERS_TO_EXPORT / self::MAX_BULK_SIZE) && $offset < $pages);
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders. " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
        if ($success) {
            $this->_messageManager->addSuccess(__("Past orders were exported successfully. Emails will be sent to your customers within 24 hours, and you will start to receive reviews."));
            $this->_yotpoHelper->log("Yotpo - Past orders were exported successfully", "info");
        } else {
            $this->_messageManager->addError(__("An error occured, please try again later."));
            $this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders. " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
        $this->getResponse()->setBody(1);
        return;
    }
}
