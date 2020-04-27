<?php

namespace Yotpo\Yotpo\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class AbstractYotpoReviewsSummary
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var YotpoConfig
     */
    protected $_yotpoConfig;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  YotpoConfig $yotpoConfig
     * @param  Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        Registry $coreRegistry
    ) {
        $this->_context = $context;
        $this->_yotpoConfig = $yotpoConfig;
        $this->_coreRegistry = $coreRegistry;
    }

    protected function _getCategoryBottomLineHtml(Product $product)
    {
        return '<div class="yotpo bottomLine bottomline-position" data-product-id="' . $product->getId() . '" data-url="' . $product->getProductUrl() . '"></div>';
    }
}
