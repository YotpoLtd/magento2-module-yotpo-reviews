<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Stdlib\DateTime;

class Date extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        return parent::render($element);
    }
}
