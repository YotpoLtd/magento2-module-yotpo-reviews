<?php

namespace Yotpo\Yotpo\Plugin\Review\Block\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Context;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Plugin for ReviewRenderer Block
 */
class ReviewRenderer
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper
    ) {
        $this->_context = $context;
        $this->_yotpoHelper = $yotpoHelper;
    }

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
        if (!$this->_yotpoHelper->isEnabled()) {
            return $proceed($product, $templateType, $displayIfNoReviews);
        }

        $currentPage = $this->_context->getRequest()->getFullActionName();

        if ($this->_yotpoHelper->isCategoryBottomlineEnabled()) {
            if (in_array($currentPage, ['cms_index_index', 'catalog_category_view'])) {
                return $this->_yotpoHelper->getCategoryBottomLineHtml($product);
            }
        } elseif (!$this->_yotpoHelper->isMdrEnabled()) {
            if (in_array($currentPage, ['cms_index_index', 'catalog_category_view'])) {
                return $proceed($product, $templateType, $displayIfNoReviews);
            }
        } else {
            return '';
        }
    }
}
