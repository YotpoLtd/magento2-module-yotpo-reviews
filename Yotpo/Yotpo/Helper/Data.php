<?php
namespace Yotpo\Yotpo\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_logger = $context->getLogger();
        parent::__construct($context);
    }

    public function showWidget($thisObj, $product = null, $print=true)
    {
        return $this->renderYotpoProductBlock($thisObj, 'widget_div', $product, $print);
    }  
    

    public function showBottomline($thisObj, $product = null, $print=true)
    {
        return $this->renderYotpoProductBlock($thisObj, 'bottomline', $product, $print);
    }  

    private function renderYotpoProductBlock($thisObj, $blockName, $product = null, $print=true)
    {
        $block = $thisObj->getLayout()->getBlock($blockName);
        if ($block == null) {
            $this->_logger->addDebug('can\'t find yotpo block');
            return;
        }
        $block->setAttribute('fromHelper', true);

        if ($product != null)
        {
            $block->setAttribute('product', $product);
        }

        if ($print == true) {
            echo $block->toHtml();
            $block->setAttribute('fromHelper', false);
        } else {
            $ret = $block->toHtml();
            $block->setAttribute('fromHelper', false);
            return $ret;
        }        
    }      
}