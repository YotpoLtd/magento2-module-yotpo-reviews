<?php
namespace Yotpo\Yotpo\Controller\Adminhtml\External;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Reviews extends \Magento\Backend\App\Action
{
    /**
     * initialize:
     */
    private $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    private $scopeId = 0;
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
        if (($this->scopeId = $this->getRequest()->getParam("store", null))) {
            $this->scope = ScopeInterface::SCOPE_STORE;
        } elseif (($this->scopeId = $this->getRequest()->getParam("website", null))) {
            $this->scope = ScopeInterface::SCOPE_WEBSITE;
        }
        if (!$this->yotpoConfig->isActivated($this->scopeId, $this->scope)) {
            $this->scope = ScopeInterface::SCOPE_STORE;
            foreach ($this->yotpoConfig->getAllStoreIds(true) as $scopeId) {
                $this->scopeId = $scopeId;
                if ($this->yotpoConfig->isActivated($this->scopeId, $this->scope)) {
                    $this->appKey = $this->yotpoConfig->getAppKey($this->scopeId, $this->scope);
                    break;
                }
            }
        } else {
            $this->appKey = $this->yotpoConfig->getAppKey($this->scopeId, $this->scope);
        }
    }
    public function execute()
    {
        $this->initialize();
        if ($this->appKey) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl('https://yap.yotpo.com/?utm_source=MagentoAdmin_ReportingReviews#/moderation/reviews');
        } else {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl('https://www.yotpo.com/integrations/magento/?utm_source=MagentoAdmin_ReportingReviews');
        }
    }
}
