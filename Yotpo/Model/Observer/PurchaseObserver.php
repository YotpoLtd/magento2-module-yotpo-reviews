<?php

namespace Yotpo\Yotpo\Model\Observer;

use Magento\Framework\Event\Observer;

class PurchaseObserver
{

	public function dispatch(Observer $observer)
	{
		try {
			$order = $observer->getEvent()->getOrder();
			$store_id = $order->getStoreId();
	        $data["email"] = $order->getCustomerEmail();
			$data["customer_name"] = $order->getCustomerName();
			$data["order_id"] = $order->getIncrementId();
			$data['platform'] = 'magento';
			$data['currency_iso'] = $order->getOrderCurrency()->getCode();
			$data["order_date"] = $order->getCreatedAt();
		} catch (Exception $e) {
			//Nothing to do here
		}
	}
}