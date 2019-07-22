<?php

namespace Yotpo\Yotpo\Plugin\Catalog\Block\Product;

use Magento\Catalog\Model\Product;
use Yotpo\Yotpo\Plugin\AbstractYotpoReviewsSummary;

/**
 * Plugin for ListProduct Block
 */
class ListProduct extends AbstractYotpoReviewsSummary
{

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
        if (!$this->_yotpoConfig->isEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        }

        if ($this->_yotpoConfig->isCategoryBottomlineEnabled()) {
            return $this->_getCategoryBottomLineHtml($product);
        } elseif (!$this->_yotpoConfig->isMdrEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        } else {
            return '';
        }
    }
}
