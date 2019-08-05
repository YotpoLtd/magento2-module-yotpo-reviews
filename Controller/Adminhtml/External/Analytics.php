<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\External;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Analytics extends \Magento\Backend\App\Action
{
    /**
     * initialize:
     */
    private $scope;
    private $scopeId;
    private $appKey;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * Constructor
     *
     * @param Context $context
     * @param YotpoConfig $yotpoConfig
     */
    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig
    ) {
        parent::__construct($context);
        $this->yotpoConfig = $yotpoConfig;
    }

    private function initialize()
    {
        if (($storeId = $this->getRequest()->getParam("store", 0))) {
            $this->scope = ScopeInterface::SCOPE_STORE;
            $this->scopeId = $storeId;
        } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
            $this->scope = ScopeInterface::SCOPE_WEBSITE;
            $this->scopeId = $websiteId;
        }

        if (!$this->yotpoConfig->isActivated($this->scopeId, $this->scope)) {
            $this->scope = ScopeInterface::SCOPE_STORE;
            foreach ($this->yotpoConfig->getAllStoreIds(true) as $storeId) {
                $this->scopeId = $storeId;
                if ($this->yotpoConfig->isActivated($this->scopeId, $this->scope)) {
                    $this->appKey = $this->yotpoConfig->getAppKey($this->scopeId, $this->scope);
                    break;
                }
            }
        }
    }

    public function execute()
    {
        $this->initialize();
        if ($this->appKey) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl('https://yap.yotpo.com/?utm_source=MagentoAdmin_ReportingAnalytics#/tools/conversions_dashboard/engagement');
        } else {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl('https://www.yotpo.com/integrations/magento/?utm_source=MagentoAdmin_ReportingAnalytics');
        }
    }
}
