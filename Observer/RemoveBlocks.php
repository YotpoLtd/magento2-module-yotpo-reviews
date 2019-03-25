<?php

namespace Yotpo\Yotpo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class RemoveBlocks implements ObserverInterface
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        YotpoHelper $yotpoHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;
    }

    public function execute(Observer $observer)
    {

        /**
 * @var \Magento\Framework\View\Layout $layout 
*/
        $layout = $observer->getLayout();

        if ($this->_yotpoHelper->isEnabled() && $this->_yotpoHelper->isMdrEnabled() && $layout->getBlock('reviews.tab')) {
            $layout->unsetElement('reviews.tab');
        }

        return $this;
    }
}
