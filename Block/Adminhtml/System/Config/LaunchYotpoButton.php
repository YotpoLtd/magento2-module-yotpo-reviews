<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class LaunchYotpoButton extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::system/config/launch_yotpo_button.phtml';

    /**
     * @var \Yotpo\Yotpo\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var Http
     */
    protected $_request;

    protected $_websiteId;
    protected $_storeId;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  YotpoHelper $yotpoHelper
     * @param  Http        $request
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        Http $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_request = $request;
        $this->_websiteId = $request->getParam('website');
        $this->_storeId = $this->getRequest()->getParam('store');
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

    /*public function getSwellGuid()
    {
        if (!is_null($this->_storeId)) {
            return $this->_yotpoHelper->getSwellGuid(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif (!is_null($this->_websiteId)) {
            return $this->_yotpoHelper->getSwellGuid(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellGuid();
        }
    }

    public function getSwellApiKey()
    {
        if (!is_null($this->_storeId)) {
            return $this->_yotpoHelper->getSwellApiKey(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif (!is_null($this->_websiteId)) {
            return $this->_yotpoHelper->getSwellApiKey(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellApiKey();
        }
    }*/

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'launch_yotpo_button',
                'label' => __('Launch Yotpo')
            ]
        );
        /*if (!($guid = $this->getSwellGuid()) || !($apiKey = $this->getSwellApiKey())) {
            $button->setOnClick("window.open('https://app.swellrewards.com/login','_blank');");
        } else {
            $button->setOnClick("window.open('https://app.swellrewards.com/login/{$guid}/{$apiKey}','_blank');");
        }*/

        return $button->toHtml();
    }
}
