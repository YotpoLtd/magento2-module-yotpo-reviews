<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
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

    public function getAppKey()
    {
        if ($this->_storeId !== null) {
            return $this->_yotpoHelper->getAppKey($this->_storeId, ScopeInterface::SCOPE_STORE);
        } elseif ($this->_websiteId !== null) {
            return $this->_yotpoHelper->getAppKey($this->_websiteId, ScopeInterface::SCOPE_WEBSITE);
        } else {
            return $this->_yotpoHelper->getAppKey();
        }
    }

    /**
     * Generate yotpo button html
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
            'class' => 'launch-yotpo-button yotpo-cta-add-arrow',
            'label' => __('Launch Yotpo'),
            ]
        );
        if (!($appKey = $this->getAppKey())) {
            $button->setDisabled(true);
        } else {
            $button->setOnClick("window.open('https://yap.yotpo.com/#/preferredAppKey={$appKey}','_blank');");
        }

        return $button->toHtml();
    }

    public function isStoreScope()
    {
        return $this->getRequest()->getParam('store');
    }
}
