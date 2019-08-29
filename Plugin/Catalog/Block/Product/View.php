<?php

namespace Yotpo\Yotpo\Plugin\Catalog\Block\Product;

use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Plugin for product View Block
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
     * @param \Magento\Catalog\Block\Product\View $viewBlock
     *
     * @return array
     */
    public function beforeToHtml(
        \Magento\Catalog\Block\Product\View $viewBlock
    ) {
        /**
         * @var \Magento\Framework\View\Layout $layout
        */
        $layout = $viewBlock->getLayout();

        if ($this->yotpoConfig->isEnabled() && $this->yotpoConfig->isMdrEnabled() && $layout->getBlock('reviews.tab')) {
            $layout->unsetElement('reviews.tab');
        }
    }
}
