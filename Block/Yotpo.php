<?php
namespace Yotpo\Yotpo\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Yotpo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  YotpoHelper $yotpoHelper
     * @param  Registry    $registry
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        array $data = []
    ) {
        $this->_yotpoHelper = $yotpoHelper;
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

    /**
     * @method getAppKey
     * @return string|null
     */
    public function getAppKey()
    {
        return $this->_yotpoHelper->getAppKey();
    }

    public function getProduct()
    {
        return $this->_yotpoHelper->getCurrentProduct();
    }

    public function hasProduct()
    {
        return $this->getProduct() && $this->getProduct()->getId();
    }

    public function getProductId()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->getProduct()->getId();
    }

    public function getProductName()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->escapeString($this->getProduct()->getName());
    }

    public function getProductDescription()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->escapeString($this->getProduct()->getShortDescription());
    }

    public function getProductUrl()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->getProduct()->getProductUrl();
    }

    public function getProductFinalPrice()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->getProduct()->getFinalPrice();
    }

    public function getProductImageUrl()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->_yotpoHelper->getProductMainImageUrl($this->getProduct());
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_yotpoHelper->getStoreManager()->getStore()->getCurrentCurrency()->getCode();
    }

    public function isRenderWidget()
    {
        return $this->hasProduct() && ($this->_yotpoHelper->isWidgetEnabled() || $this->getData('fromHelper'));
    }

    public function isRenderBottomline()
    {
        return $this->_yotpoHelper->isBottomlineEnabled();
    }

    public function isRenderBottomlineQna()
    {
        return $this->_yotpoHelper->isBottomlineQnaEnabled();
    }

    public function escapeString($str)
    {
        return $this->_escaper->escapeHtml(strip_tags($str));
    }

    public function getYotpoWidgetUrl()
    {
        return $this->_yotpoHelper->getYotpoWidgetUrl();
    }
}
