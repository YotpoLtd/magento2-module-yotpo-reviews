<?php

namespace Yotpo\Yotpo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class RemoveBlocks implements ObserverInterface
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

    public function execute(Observer $observer)
    {

        /**
 * @var \Magento\Framework\View\Layout $layout 
*/
        $layout = $observer->getLayout();

        if ($this->yotpoConfig->isEnabled() && $this->yotpoConfig->isMdrEnabled() && $layout->getBlock('reviews.tab')) {
            $layout->unsetElement('reviews.tab');
        }

        return $this;
    }
}
