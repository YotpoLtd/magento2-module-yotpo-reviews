<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Reviews extends \Magento\Backend\App\Action
{
    /**
     * initialize:
     */
    protected $_scope;
    protected $_scopeId;
    protected $_isEnabled;
    protected $_appKey;
    protected $_isAppKeyAndSecretSet;

    /**
    * @var PageFactory
    */
    protected $resultPageFactory;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param YotpoHelper $yotpoHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        YotpoHelper $yotpoHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
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

    /**
     * Load the page defined in view/adminhtml/layout/yotpo_yotpo_report_reviews.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Yotpo Reviews'));
        if (!($this->_isEnabled && $this->_isAppKeyAndSecretSet)) {
            $resultPage->getLayout()->unsetElement('store_switcher');
        }
        return $resultPage;
    }
}
