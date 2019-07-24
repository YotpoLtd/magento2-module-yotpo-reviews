<?php

namespace Yotpo\Yotpo\Plugin\Review\Block\Product;

use Magento\Catalog\Model\Product;
use Yotpo\Yotpo\Plugin\AbstractYotpoReviewsSummary;

/**
 * Plugin for ReviewRenderer Block
 */
class ReviewRenderer extends AbstractYotpoReviewsSummary
{
    /**
     * Get review summary html
     *
     * @param \Magento\Review\Block\Product\ReviewRenderer $reviewRendererBlock
     * @param callable                                     $proceed
     * @param Product                                      $product
     * @param string                                       $templateType
     * @param bool                                         $displayIfNoReviews
     *
     * @return string
     */
    public function aroundGetReviewsSummaryHtml(
        \Magento\Review\Block\Product\ReviewRenderer $reviewRendererBlock,
        callable $proceed,
        Product $product,
        $templateType = \Magento\Review\Block\Product\ReviewRenderer::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        if (!$this->_yotpoConfig->isEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        }

        $currentPage = $this->_context->getRequest()->getFullActionName();

        if ($this->_yotpoConfig->isCategoryBottomlineEnabled()) {
            if (in_array($currentPage, ['cms_index_index', 'catalog_category_view'])) {
                return $this->_getCategoryBottomLineHtml($product);
            }
        } elseif (!$this->_yotpoConfig->isMdrEnabled()) {
            if (in_array($currentPage, ['cms_index_index', 'catalog_category_view'])) {
                return $proceed($product, $templateType, $displayIfNoReviews);
            }
        } else {
            return '';
        }
    }
}
