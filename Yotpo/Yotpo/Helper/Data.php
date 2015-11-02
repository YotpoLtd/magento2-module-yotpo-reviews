<?php
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

    public function showWidget($thisObj, $product = null, $print=true)
    {
        $this->_logger->addDebug('YOTPO                      showWidget');
        return $this->renderYotpoProductBlock($thisObj, 'widget_div', $product, $print);
    }  
    

    public function showBottomline($thisObj, $product = null, $print=true)
    {
        $this->_logger->addDebug('YOTPO                      showBottomline');
        return $this->renderYotpoProductBlock($thisObj, 'bottomline', $product, $print);
    }  

    private function renderYotpoProductBlock($thisObj, $blockName, $product = null, $print=true)
    {
        $block = $thisObj->getLayout()->getBlock($blockName);
        if ($block == null) {
            $this->_logger->addDebug('can\'t find yotpo block');
            return;
        }
        $this->_logger->addDebug('YOTPO                 block:     '.$blockName);
        // $this->_logger->addDebug('YOTPO                      '.json_encode(get_class_methods($block)));
        $block->setAttribute('fromHelper', true);

        if ($product != null)
        {
            $this->_logger->addDebug('YOTPO                 got product obj     '.json_encode($product));
            $block->setAttribute('product', $product);
        }

        $this->_logger->addDebug('YOTPO                 $block->toHtml     '.json_encode($block->toHtml()));
        
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