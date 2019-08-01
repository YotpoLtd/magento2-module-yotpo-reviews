<?php

namespace Yotpo\Yotpo\Plugin\Catalog\Block\Product\View;

use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Plugin for product Details Block
 */
class Details
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
     * @param \Magento\Catalog\Block\Product\View\Details $reviewBlock
     *
     * @return array
     */
    public function beforeToHtml(
        \Magento\Catalog\Block\Product\View\Details $reviewBlock
    ) {
        /**
         * @var \Magento\Framework\View\Layout $layout
        */
        $layout = $reviewBlock->getLayout();

        if ($this->yotpoConfig->isEnabled() && $this->yotpoConfig->isMdrEnabled() && $layout->getBlock('reviews.tab')) {
            $layout->unsetElement('reviews.tab');
        }
    }
}
