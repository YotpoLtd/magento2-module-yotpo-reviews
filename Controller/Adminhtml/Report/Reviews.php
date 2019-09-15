<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Reviews extends \Magento\Backend\App\Action
{
    /**
     * initialize:
     */
    private $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    private $scopeId = 0;
    private $isEnabled;
    private $appKey;
    private $isAppKeyAndSecretSet;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param YotpoConfig $yotpoConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        YotpoConfig $yotpoConfig
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->yotpoConfig = $yotpoConfig;
    }

    private function initialize()
    {
        if (($storeId = $this->getRequest()->getParam(ScopeInterface::SCOPE_STORE, 0))) {
            $this->allStoreIds = [$storeId];
        } elseif (($websiteId = $this->getRequest()->getParam(ScopeInterface::SCOPE_WEBSITE, 0))) {
            $this->allStoreIds = $this->yotpoConfig->getStoreManager()->getWebsite($websiteId)->getStoreIds();
        } else {
            $this->allStoreIds = $this->yotpoConfig->getAllStoreIds(false);
        }
        $this->allStoreIds = $this->yotpoConfig->filterDisabledStoreIds($this->allStoreIds);
        $this->scopeId = ($this->allStoreIds) ? $this->allStoreIds[0] : 0;

        $this->isEnabled = ($this->allStoreIds) ? true : false;
        $this->isAppKeyAndSecretSet = ($this->allStoreIds) ? true : false;
        $this->appKey = ($this->scopeId) ? $this->yotpoConfig->getAppKey($this->scopeId, $this->scope) : null;
    }

    /**
     * Load the page defined in view/adminhtml/layout/yotpo_yotpo_report_reviews.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->initialize();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Yotpo Reviews'));
        if (!($this->isEnabled && $this->isAppKeyAndSecretSet)) {
            $resultPage->getLayout()->unsetElement('store_switcher');
        }
        return $resultPage;
    }
}
