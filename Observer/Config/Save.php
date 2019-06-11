<?php

namespace Yotpo\Yotpo\Observer\Config;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface as AppScopeInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Save implements ObserverInterface
{
    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $_appConfig;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var ResourceConfig
     */
    protected $_resourceConfig;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @param TypeListInterface         $cacheTypeList
     * @param ReinitableConfigInterface $config
     * @param ResourceConfig            $resourceConfig
     * @param YotpoHelper               $yotpoHelper
     * @param YotpoApiClient            $yotpoApi
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config,
        ResourceConfig $resourceConfig,
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi
    ) {
        $this->_cacheTypeList = $cacheTypeList;
        $this->_appConfig = $config;
        $this->_resourceConfig = $resourceConfig;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getChangedPaths();
        if ($changedPaths) {
            $this->_cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->_appConfig->reinit();

            $scope = $scopes = null;
            if (($scopeId = $observer->getEvent()->getStore())) {
                $scope = ScopeInterface::SCOPE_STORE;
                $scopes = ScopeInterface::SCOPE_STORES;
            } elseif (($scopeId = $observer->getEvent()->getWebsite())) {
                $scope = ScopeInterface::SCOPE_WEBSITE;
            }

            if (in_array(YotpoHelper::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $changedPaths)) {
                $this->_yotpoHelper->log(
                    "Yotpo Debug mode " . (($this->_yotpoHelper->isDebugMode(($scopeId ?: null), ($scope ?: null))) ? 'started' : 'stopped'),
                    "error",
                    ['$app_key' => $this->_yotpoHelper->getAppKey(($scopeId ?: null), ($scope ?: null)), '$scope' => ($scope ?: 'default'), '$scopeId' => $scopeId]
                );
            }

            if ($scope !== ScopeInterface::SCOPE_STORE) {
                return true;
            }
            if ($this->_yotpoHelper->isEnabled(($scopeId ?: null), ($scope ?: null)) && !($this->_yotpoApi->oauthAuthentication(($scopeId ?: null), ($scope ?: null)))) {
                $this->_resourceConfig->saveConfig(YotpoHelper::XML_PATH_YOTPO_APP_KEY, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                $this->_resourceConfig->saveConfig(YotpoHelper::XML_PATH_YOTPO_SECRET, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                $this->_resourceConfig->saveConfig(YotpoHelper::XML_PATH_YOTPO_ENABLED, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
            }
        }
    }
}
