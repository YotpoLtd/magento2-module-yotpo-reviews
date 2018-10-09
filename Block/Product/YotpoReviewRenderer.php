<?php

namespace Yotpo\Yotpo\Block\Product;

class YotpoReviewRenderer extends \Magento\Review\Block\Product\ReviewRenderer
{
    const SCOPE_STORE   = 'store';
    const YOTPO_BOTTOMLINE_CATEGORY_ENABLED = 'yotpo/settings/category_bottomline_enabled';
    const MAGENTO_DEFAULT_REVIEWS_ENABLED = 'yotpo/settings/mdr_enabled';
    protected $_availableTemplates = [
        self::FULL_VIEW => 'Magento_Review::helper/summary.phtml',
        self::SHORT_VIEW => 'Magento_Review::helper/summary_short.phtml',
    ];
    
    public function getReviewsSummaryHtml(
        \Magento\Catalog\Model\Product $product,
        $templateType = self::DEFAULT_VIEW,
        $displayIfNoReviews = false
    ) {
        
        $enableBottomlineCategoryPage = $this->isBottomlineCategoryEnabled();
	$enableMagentoDefaultReviews = $this->isMagentoDefaultReviewsEnabled();
        if ($enableBottomlineCategoryPage) {
            return $this->showCategoryBottomLine($product);
        } elseif (!$enableMagentoDefaultReviews) {
            return parent::getReviewsSummaryHtml($product, $templateType, $displayIfNoReviews);
        } else {
            return '';
        }
        
         
    }
    
    public function isMagentoDefaultReviewsEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::MAGENTO_DEFAULT_REVIEWS_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function isBottomlineCategoryEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_BOTTOMLINE_CATEGORY_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function showCategoryBottomLine($product)
    {        
        return '<div style="float:left; padding-bottom:10px;" class="yotpo bottomLine" data-product-id="'.$product->getId().'"
	data-url="'.$product->getUrl().'">
        </div>';
    }
}
