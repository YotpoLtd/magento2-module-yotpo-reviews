<?php

namespace Yotpo\Yotpo\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Core\Model\ObjectManager;


class PurchaseObserver 
{   
	public function __construct(\Yotpo\Yotpo\Helper\ApiClient $helper)
	{
	    $this->_helper = $helper;            
	}
    //observer function hooked on event sales_order_save_after
    public function dispatch(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $store_id = $order->getStoreId();
            $data['email'] = $order->getCustomerEmail();
            $data['customer_name'] = $order->getCustomerName();
            $data['order_id'] = $order->getIncrementId();
            $data['platform'] = 'magento';
            $data['currency_iso'] = $order->getOrderCurrency()->getCode();
            $data['order_date'] = $order->getCreatedAt();        
            $data['products'] = $this->_helper->prepareProductsData($order); 
            $data['utoken'] = $this->_helper->oauthAuthentication($store_id);
            if ($data['utoken'] == null) {
                //failed to get access token to api
                // Mage::log('access token recieved from yotpo api is null');  //TODO change to magento logging
                return $this;
            }
            $this->_helper->createPurchases($data, $store_id); 
            return $this;   
        } catch(Exception $e) {
            // Mage::log('Failed to send mail after purchase. Error: '.$e); //TODO change to magento logging
        }

    }
}