<?php

namespace Yotpo\Yotpo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\OrderStatusHistoryFactory;

class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var YotpoConfig
     */
    private $orderStatusHistoryFactory;

    /**
     * @method __construct
     * @param  YotpoConfig               $yotpoConfig
     * @param  OrderStatusHistoryFactory $orderStatusHistoryFactory
     */
    public function __construct(
        YotpoConfig $yotpoConfig,
        OrderStatusHistoryFactory $orderStatusHistoryFactory
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if ($order->getOrigData('status') !== $order->getData('status')) {
                $this->orderStatusHistoryFactory->create()
                    ->setOrderId($order->getId())
                    ->setStoreId($order->getStoreId())
                    ->setOldStatus($order->getOrigData('status'))
                    ->setNewStatus($order->getData('status'))
                    ->setCreatedAt($this->yotpoConfig->getCurrentDate())
                    ->save();
            }
        } catch (\Exception $e) {
            $this->yotpoConfig->log("OrderSaveAfter::execute() - Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
