<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\External;

use Magento\Backend\App\Action\Context;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Analytics extends \Magento\Backend\App\Action
{
    protected $_scope;
    protected $_scopeId;
    protected $_isEnabled;
    protected $_appKey;
    protected $_isAppKeyAndSecretSet;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param YotpoHelper $yotpoHelper
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper
    ) {
        parent::__construct($context);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_initiaize();
    }

    protected function _initiaize()
    {
        if (($storeId = $this->getRequest()->getParam("store", 0))) {
            $this->_scope = ScopeInterface::SCOPE_STORE;
            $this->_scopeId = $storeId;
        } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
            $this->_scope = ScopeInterface::SCOPE_WEBSITE;
            $this->_scopeId = $websiteId;
        }
        $this->_isEnabled = $this->_yotpoHelper->isEnabled($this->_scopeId, $this->_scope);
        $this->_appKey = $this->_yotpoHelper->getAppKey($this->_scopeId, $this->_scope);
        $this->_isAppKeyAndSecretSet = $this->_yotpoHelper->isAppKeyAndSecretSet($this->_scopeId, $this->_scope);
    }

    public function execute()
    {
        return $this->getResponse()->setContent("Analytics");
    }
}
