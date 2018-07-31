<?php

namespace Yotpo\Yotpo\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RemoveBlock implements ObserverInterface {

    protected $_scopeConfig;

    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->_scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer) {

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $observer->getLayout();

        $block = $layout->getBlock('reviews.tab');
//        echo '<pre>';
//        echo $block;
//        echo '</pre>';
        if ($block) {

            $remove = $this->_scopeConfig->getValue(
                    'yotpo/settings/mdr_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            echo $remove;

            if ($remove) {

                $layout->unsetElement('reviews.tab');
            }
        }
    }

}
