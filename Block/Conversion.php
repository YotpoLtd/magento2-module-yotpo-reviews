<?php
namespace Yotpo\Yotpo\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Conversion extends \Magento\Framework\View\Element\Template
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @method __construct
     * @param  Context                  $context
     * @param  YotpoConfig              $yotpoConfig
     * @param  Session                  $checkoutSession
     * @param  OrderRepositoryInterface $orderRepository
     * @param  array                    $data
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * @method isEnabled
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->yotpoConfig->isEnabled() && $this->yotpoConfig->isAppKeyAndSecretSet();
    }

    public function getOrderId()
    {
        return $this->checkoutSession->getLastOrderId();
    }

    public function getOrder()
    {
        if (!$this->hasData('order') && $this->getOrderId()) {
            $this->setData('order', $this->orderRepository->get($this->getOrderId()));
        }
        return $this->getData('order');
    }

    public function hasOrder()
    {
        return $this->getOrder() && $this->getOrder()->getId();
    }

    public function getOrderAmount()
    {
        if (!$this->hasOrder()) {
            return null;
        }
        return $this->getOrder()->getSubtotal();
    }

    public function getOrderCurrency()
    {
        if (!$this->hasOrder()) {
            return null;
        }
        return $this->getOrder()->getOrderCurrencyCode();
    }

    /**
     * @method getJsonData
     * @return string|null
     */
    public function getJsonData()
    {
        if (!($this->hasOrder() && $this->yotpoConfig->getAppKey())) {
            return null;
        }
        return json_encode(
            [
            "orderId" => $this->getOrder()->getIncrementId(),
            "orderAmount" => $this->getOrderAmount(),
            "orderCurrency" => $this->getOrderCurrency(),
            ]
        );
    }

    /**
     * @method getNoscriptSrc
     * @return string|null
     */
    public function getNoscriptSrc()
    {
        if (!($this->hasOrder() && $this->yotpoConfig->getAppKey())) {
            return null;
        }
        return $this->yotpoConfig->getYotpoNoSchemaApiUrl(
            "conversion_tracking.gif?" . http_build_query(
                [
                "app_key" => $this->yotpoConfig->getAppKey(),
                "order_id" => $this->getOrderId(),
                "order_amount" => $this->getOrderAmount(),
                "order_currency" => $this->getOrderCurrency(),
                ]
            )
        );
    }
}
