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

    public function showWidget($thisObj, $product = null)
    {
        return $this->renderYotpoProductBlock($thisObj, 'yotpo_widget_div', $product);
    }  
    
    public function showBottomline($thisObj, $product = null)
    {
        return $this->renderYotpoProductBlock($thisObj, 'yotpo_bottomline', $product);
    }

    private function renderYotpoProductBlock($thisObj, $blockName, $product = null)
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

        $ret = $block->toHtml();
        $block->setAttribute('fromHelper', false);
        return $ret;
    }
}
