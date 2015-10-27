<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Yotpo\Yotpo\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        parent::__construct($context);
    }

    public function showWidget($thisObj, $print=true)
    {
        $res = $this->renderYotpoProductBlock($thisObj, 'widget_div', $print);
        if ($print == false) {
            return $res;
        }
    }  
    
    private function renderYotpoProductBlock($thisObj, $blockName, $print=true)
    {
        $block = $thisObj->getLayout()->getBlock($blockName);
        if ($block == null) {
            $this->_logger->addDebug('can\'t find yotpo block1');
            return;
        }

        $block->setAttribute('from_code', true);
        if ($print == true) {
            echo $block->toHtml();
            $block->setAttribute('from_code', false);
        } else {
            $ret = $block->toHtml();
            $block->setAttribute('from_code', false);
            return $ret;
        }        
    }      
}