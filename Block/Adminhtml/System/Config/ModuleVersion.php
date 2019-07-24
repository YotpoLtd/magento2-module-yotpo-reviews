<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class ModuleVersion extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::system/config/module_version.phtml';

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @param  Context     $context
     * @param  YotpoConfig $yotpoConfig
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig,
        array $data = []
    ) {
        $this->yotpoConfig = $yotpoConfig;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->yotpoConfig->getModuleVersion();
    }
}
