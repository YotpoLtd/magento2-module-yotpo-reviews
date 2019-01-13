<?php
namespace Yotpo\Yotpo\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Conversion extends \Magento\Framework\View\Element\Template
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @method __construct
     * @param  Context                  $context
     * @param  YotpoHelper              $yotpoHelper
     * @param  Session                  $checkoutSession
     * @param  OrderRepositoryInterface $orderRepository
     * @param  array                    $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * @method isEnabled
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_yotpoHelper->isEnabled() && $this->_yotpoHelper->isAppKeyAndSecretSet();
    }

    public function getOrderId()
    {
        return $this->_checkoutSession->getLastOrderId();
    }

    public function getOrder()
    {
        if (!$this->hasData('order') && $this->getOrderId()) {
            $this->setData('order', $this->_orderRepository->get($this->getOrderId()));
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
        if (!($this->hasOrder() && $this->_yotpoHelper->getAppKey())) {
            return null;
        }
        return json_encode(
            [
            "orderId" => $this->getOrderId(),
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
        if (!($this->hasOrder() && $this->_yotpoHelper->getAppKey())) {
            return null;
        }
        return $this->_yotpoHelper->getYotpoNoSchemaApiUrl(
            "conversion_tracking.gif?" . http_build_query(
                [
                "app_key" => $this->_yotpoHelper->getAppKey(),
                "order_id" => $this->getOrderId(),
                "order_amount" => $this->getOrderAmount(),
                "order_currency" => $this->getOrderCurrency(),
                ]
            )
        );
    }
}
