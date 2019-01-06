<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Date extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @param Context $context
     * @param YotpoHelper $yotpoHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        array $data = []
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        $element->setMinDate($this->_yotpoHelper->getOrdersSyncAfterMinDate());
        return parent::render($element);
    }
}
