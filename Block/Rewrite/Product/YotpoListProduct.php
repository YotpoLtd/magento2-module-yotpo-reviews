<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Yotpo\Yotpo\Block\Rewrite\Product;

class YotpoListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    
    private $_yotpoHelper ='Yotpo\Yotpo\Helper\Data';
    const SCOPE_STORE   = 'store';
    const YOTPO_BOTTOMLINE_CATEGORY_ENABLED = 'yotpo/settings/category_bottomline_enabled';
    
     public function getReviewsSummaryHtml(
        \Magento\Catalog\Model\Product $product,
        $templateType = false,
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