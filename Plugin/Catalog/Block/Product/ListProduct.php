<?php

namespace Yotpo\Yotpo\Plugin\Catalog\Block\Product;

use Magento\Catalog\Model\Product;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Plugin for ListProduct Block
 */
class ListProduct
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        YotpoHelper $yotpoHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;
    }

    /**
     * Get product reviews summary
     *
     * @param  \Magento\Catalog\Block\Product\ListProduct $listProductBlock
     * @param  callable                                   $proceed
     * @param  Product                                    $product
     * @param  bool                                       $templateType
     * @param  bool                                       $displayIfNoReviews
     * @return string
     */
    public function aroundGetReviewsSummaryHtml(
        \Magento\Catalog\Block\Product\ListProduct $listProductBlock,
        callable $proceed,
        Product $product,
        $templateType = false,
        $displayIfNoReviews = false
    ) {
        if (!$this->_yotpoHelper->isEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        }

        if ($this->_yotpoHelper->isCategoryBottomlineEnabled()) {
            return $this->_yotpoHelper->getCategoryBottomLineHtml($product);
        } elseif (!$this->_yotpoHelper->isMdrEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        } else {
            return '';
        }
    }
}
