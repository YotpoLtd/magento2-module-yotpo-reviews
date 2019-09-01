<?php

namespace Yotpo\Yotpo\Observer\Config;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * @param  YotpoConfig               $yotpoConfig
     * @param  YotpoApi                  $yotpoApi
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config,
        YotpoConfig $yotpoConfig,
        YotpoApi $yotpoApi
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $config;
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
            $appKey = $this->yotpoConfig->getAppKey(($scopeId ?: null), ($scope ?: null));

            if (in_array(YotpoConfig::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $changedPaths)) {
                $this->yotpoConfig->log(
                    "Yotpo Debug mode " . (($this->yotpoConfig->isDebugMode(($scopeId ?: null), ($scope ?: null))) ? 'started' : 'stopped'),
                    "info",
                    ['$app_key' => $appKey, '$scope' => ($scope ?: 'default'), '$scopeId' => $scopeId]
                );
            }

            if ($scope !== ScopeInterface::SCOPE_STORE) {
                return true;
            }
            //Check if appKey is unique:
            if ($appKey) {
                foreach ($this->yotpoConfig->getAllStoreIds(false) as $key => $storeId) {
                    if ($storeId !== $scopeId && $this->yotpoConfig->getAppKey($storeId) === $appKey) {
                        $this->yotpoConfig->resetStoreCredentials($scopeId);
                        throw new \Exception(__("The APP KEY you've entered is already in use by another store on this system. Note that Yotpo requires a unique set of APP KEY & SECRET for each store."));
                    }
                }
            }

            if ($this->yotpoConfig->isEnabled(($scopeId ?: null), ($scope ?: null)) && !($this->yotpoApi->oauthAuthentication(($scopeId ?: null), ($scope ?: null)))) {
                $this->yotpoConfig->resetStoreCredentials($scopeId);
                throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
            }
        }
    }
}
