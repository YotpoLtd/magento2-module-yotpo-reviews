<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Date extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @param Context $context
     * @param YotpoConfig $yotpoConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        array $data = []
    ) {
        $this->yotpoConfig = $yotpoConfig;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        $element->setMinDate($this->yotpoConfig->getOrdersSyncAfterMinDate());
        return parent::render($element);
    }
}
