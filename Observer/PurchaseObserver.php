<?php

namespace Yotpo\Yotpo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class PurchaseObserver implements ObserverInterface
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        YotpoApiClient $yotpoApi,
        YotpoHelper $yotpoHelper
    ) {
        $this->_yotpoApi = $yotpoApi;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_logger = $this->_yotpoHelper->getLogger();
    }

    //Observer function hooked on event sales_order_save_after
    public function execute(Observer $observer)
    {
        try {
            $this->_yotpoHelper->log('Yotpo PurchaseObserver [TRIGGERED]', "info");
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            if (
                !$this->_yotpoHelper->isEnabled() ||
                !$this->_yotpoHelper->isAppKeyAndSecretSet($storeId) ||
                !in_array($order->getStatus(), $this->_yotpoHelper->getCustomOrderStatus($storeId))
            ) {
                $this->_yotpoHelper->log('Yotpo PurchaseObserver [SKIPPING]', "info");
                return $this;
            }

            $this->_yotpoHelper->log('Yotpo PurchaseObserver - preparing order data...', "info");
            $orderData = $this->_yotpoApi->prepareOrderData($order);
            if (!$orderData) {
                $this->_yotpoHelper->log('Yotpo PurchaseObserver [ERROR] - empty order data. export failed!', "error");
                return $this;
            }

            $this->_yotpoHelper->log('Yotpo PurchaseObserver - authenticating...', "info");
            $token = $this->_yotpoApi->oauthAuthentication($storeId);
            if ($token == null) {
                //Failed to get access token to api
                $this->_yotpoHelper->log('Yotpo PurchaseObserver [ERROR] - access token recieved from yotpo api is null. export failed!', "error");
                return $this;
            }

            $this->_yotpoHelper->log('Yotpo PurchaseObserver - creating purchases...', "info");
            $this->_yotpoApi->createPurchases($orderData, $token, $storeId);

            $this->_yotpoHelper->log('Yotpo PurchaseObserver [DONE]', "info");
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo PurchaseObserver - Failed to send mail after purchase. [EXCEPTION]: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
        return $this;
    }
}
