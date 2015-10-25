<?php

namespace Yotpo\Yotpo\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Core\Model\ObjectManager;


class PurchaseObserver 
{   
    
    private $logger;
    private $helper;

	public function __construct(\Yotpo\Yotpo\Helper\ApiClient $helper,
                                \Psr\Log\LoggerInterface $logger)
                        
	{
	    $this->helper = $helper; 
        $this->logger = $logger;           
	}
    //observer function hooked on event sales_order_save_after
    public function dispatch(Observer $observer)
    {
        try {
            $this->logger->addDebug('TEST STSTST'); 
            $order = $observer->getEvent()->getOrder();
            $store_id = $order->getStoreId();
            $data['email'] = $order->getCustomerEmail();
            $data['customer_name'] = $order->getCustomerName();
            $data['order_id'] = $order->getIncrementId();
            $data['platform'] = 'magento';
            $data['currency_iso'] = $order->getOrderCurrency()->getCode();
            $data['order_date'] = $order->getCreatedAt();        
            $data['products'] = $this->helper->prepareProductsData($order); 
            $data['utoken'] = $this->helper->oauthAuthentication($store_id);
            if ($data['utoken'] == null) {
                //failed to get access token to api
                $this->logger->addDebug('access token recieved from yotpo api is null');  
                return $this;
            }
            $this->helper->createPurchases($data, $store_id); 
            return $this;   
        } catch(Exception $e) {
            $this->logger->addDebug('Failed to send mail after purchase. Error: '.$e); 
        }

    }
}
