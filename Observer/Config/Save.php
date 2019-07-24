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
use Yotpo\Yotpo\Model\AbstractApi as YotpoApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Save implements ObserverInterface
{
    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    private $appConfig;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * @method __construct
     * @param  TypeListInterface         $cacheTypeList
     * @param  ReinitableConfigInterface $config
     * @param  ResourceConfig            $resourceConfig
     * @param  YotpoConfig               $yotpoConfig
     * @param  YotpoApi                  $yotpoApi
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config,
        ResourceConfig $resourceConfig,
        YotpoConfig $yotpoConfig,
        YotpoApi $yotpoApi
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $config;
        $this->resourceConfig = $resourceConfig;
        $this->yotpoConfig = $yotpoConfig;
        $this->yotpoApi = $yotpoApi;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getChangedPaths();
        if ($changedPaths) {
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->appConfig->reinit();

            $scope = $scopes = null;
            if (($scopeId = $observer->getEvent()->getStore())) {
                $scope = ScopeInterface::SCOPE_STORE;
                $scopes = ScopeInterface::SCOPE_STORES;
            } elseif (($scopeId = $observer->getEvent()->getWebsite())) {
                $scope = ScopeInterface::SCOPE_WEBSITE;
            }

            if (in_array(YotpoConfig::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $changedPaths)) {
                $this->yotpoConfig->log(
                    "Yotpo Debug mode " . (($this->yotpoConfig->isDebugMode(($scopeId ?: null), ($scope ?: null))) ? 'started' : 'stopped'),
                    "error",
                    ['$app_key' => $this->yotpoConfig->getAppKey(($scopeId ?: null), ($scope ?: null)), '$scope' => ($scope ?: 'default'), '$scopeId' => $scopeId]
                );
            }

            if ($scope !== ScopeInterface::SCOPE_STORE) {
                return true;
            }
            if ($this->yotpoConfig->isEnabled(($scopeId ?: null), ($scope ?: null)) && !($this->yotpoApi->oauthAuthentication(($scopeId ?: null), ($scope ?: null)))) {
                $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_APP_KEY, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_SECRET, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_ENABLED, null, ($scopes ?: AppScopeInterface::SCOPE_DEFAULT), ($scopeId ?: 0));
                throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
            }
        }
    }
}
