<?php

namespace Yotpo\Yotpo\Block\Product;

class YotpoReviewRenderer extends \Magento\Review\Block\Product\ReviewRenderer
{
    const SCOPE_STORE   = 'store';
    const YOTPO_BOTTOMLINE_CATEGORY_ENABLED = 'yotpo/settings/category_bottomline_enabled';
    
    public function getReviewsSummaryHtml(
        \Magento\Catalog\Model\Product $product,
        $templateType = self::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        
        $enableBottomlineCategoryPage = $this->isBottomlineCategoryEnabled();
	
        if ($enableBottomlineCategoryPage) {
            return $this->showCategoryBottomLine($product);
        } else {
            return parent::getReviewsSummaryHtml($product, $templateType, $displayIfNoReviews);
        }
        
         
    }
    
    public function isBottomlineCategoryEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_BOTTOMLINE_CATEGORY_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function showCategoryBottomLine($product)
    {        
        return '<div class="yotpo bottomLine" data-product-id="'.$product->getId().'"
	data-url="'.$product->getUrl().'">
        </div>';
    }
}