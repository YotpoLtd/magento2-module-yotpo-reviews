<?php
namespace Yotpo\Yotpo\Block;

use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Yotpo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CatalogImageHelper
     */
    private $catalogImageHelper;

    /**
     * @method __construct
     * @param  Context            $context
     * @param  YotpoConfig        $yotpoConfig
     * @param  Registry           $coreRegistry
     * @param  CatalogImageHelper $catalogImageHelper
     * @param  array              $data
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        Registry $coreRegistry,
        CatalogImageHelper $catalogImageHelper,
        array $data = []
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->coreRegistry = $coreRegistry;
        $this->catalogImageHelper = $catalogImageHelper;
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

    /**
     * @method getAppKey
     * @return string|null
     */
    public function getAppKey()
    {
        return $this->yotpoConfig->getAppKey();
    }

    public function getProduct()
    {
        if ($this->getData('product') === null) {
            $this->setData('product', $this->coreRegistry->registry('current_product'));
        }
        return $this->getData('product');
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

    /**
     * @method getProductImageUrl
     * @param  string  $imageId
     * @return string|null
     */
    public function getProductImageUrl($imageId = 'product_page_image_large')
    {
        if (!$this->hasProduct()) {
            return null;
        }
        return $this->catalogImageHelper->init($this->getProduct(), $imageId)->getUrl();
    }

    public function getCurrentCurrencyCode()
    {
        return $this->yotpoConfig->getStoreManager()->getStore()->getCurrentCurrency()->getCode();
    }

    public function isRenderWidget()
    {
        return $this->hasProduct() && ($this->yotpoConfig->isWidgetEnabled() || $this->getData('fromHelper'));
    }

    public function isRenderBottomline()
    {
        return $this->hasProduct() && ($this->yotpoConfig->isBottomlineEnabled() || $this->getData('fromHelper'));
    }

    public function isRenderBottomlineQna()
    {
        return $this->hasProduct() && $this->yotpoConfig->isBottomlineQnaEnabled();
    }

    public function escapeString($str)
    {
        return $this->_escaper->escapeHtml(strip_tags($str));
    }

    public function getYotpoWidgetUrl()
    {
        return $this->yotpoConfig->getYotpoWidgetUrl();
    }
}
