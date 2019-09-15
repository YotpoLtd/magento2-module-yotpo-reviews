<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class NoScopes extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue()->unsCanRestoreToDefault();
        return parent::render($element);
    }
}
