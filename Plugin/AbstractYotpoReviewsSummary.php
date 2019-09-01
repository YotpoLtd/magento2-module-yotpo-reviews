<?php

namespace Yotpo\Yotpo\Plugin;

use Magento\Catalog\Model\Product;
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

    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig
    ) {
        $this->_context = $context;
        $this->_yotpoConfig = $yotpoConfig;
    }

    protected function _getCategoryBottomLineHtml(Product $product)
    {
        return '<div class="yotpo bottomLine bottomline-position" data-product-id="' . $product->getId() . '" data-url="' . $product->getProductUrl() . '"></div>';
    }
}
