<?php

namespace Yotpo\Yotpo\Model\Observer;

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
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();
            if (
                !$this->_yotpoHelper->isEnabled() ||
                !$this->_yotpoHelper->isAppKeyAndSecretSet($storeId) ||
                $order->getStatus() !== Order::STATE_COMPLETE
            ) {
                return $this;
            }
            $data = $this->_yotpoApi->prepareOrderData($order);
            $data['utoken'] = $this->_yotpoApi->oauthAuthentication($storeId);
            if ($data['utoken'] == null) {
                //Failed to get access token to api
                $this->_yotpoHelper->log('Yotpo PurchaseObserver error - access token recieved from yotpo api is null', "error");
                return $this;
            }
            $this->_yotpoApi->createPurchases($data, $storeId);
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo PurchaseObserver - Failed to send mail after purchase. Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
        return $this;
    }
}
