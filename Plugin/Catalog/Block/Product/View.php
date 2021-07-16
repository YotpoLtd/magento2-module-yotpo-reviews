<?php

namespace Yotpo\Yotpo\Plugin\Catalog\Block\Product;

use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Plugin for product Details Block
 */
class View
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    public function __construct(
        YotpoConfig $yotpoConfig
    ) {
        $this->yotpoConfig = $yotpoConfig;
    }

    /**
     * @method beforeToHtml
     * @param \Magento\Catalog\Block\Product\View $reviewBlock
     *
     * @return array
     */
    public function beforeToHtml(
        \Magento\Catalog\Block\Product\View $reviewBlock
    ) {
        /**
         * @var \Magento\Framework\View\Layout $layout
        */
        $layout = $reviewBlock->getLayout();
        if ($this->yotpoConfig->isEnabled() && $this->yotpoConfig->isMdrEnabled()) {
            if ($layout->getBlock('product.reviews.wrapper')) {
                $layout->unsetElement('product.reviews.wrapper');
            }
            if ($layout->getBlock('reviews.tab')) {
                $layout->unsetElement('reviews.tab');
            }
        }
    }
}
