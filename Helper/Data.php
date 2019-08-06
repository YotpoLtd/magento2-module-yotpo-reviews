<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\AbstractBlock;

class Data extends AbstractHelper
{
    private function renderYotpoProductBlock($blockName, AbstractBlock $parentBlock, Product $product = null)
    {
        return $parentBlock->getLayout()->createBlock(\Yotpo\Yotpo\Block\Yotpo::class)
          ->setTemplate('Yotpo_Yotpo::' . $blockName . '.phtml')
          ->setAttribute('product', $product)
          ->setAttribute('fromHelper', true)
          ->toHtml();
    }

    public function showWidget(AbstractBlock $parentBlock, Product $product = null)
    {
        return $this->renderYotpoProductBlock('widget_div', $parentBlock, $product);
    }

    public function showBottomline(AbstractBlock $parentBlock, Product $product = null)
    {
        return $this->renderYotpoProductBlock('bottomline', $parentBlock, $product);
    }
}
