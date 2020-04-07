<?php

namespace Yotpo\Yotpo\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Config
{
    const MODULE_NAME = 'Yotpo_Yotpo';

    //= General Settings
    const XML_PATH_YOTPO_ALL = 'yotpo';
    const XML_PATH_YOTPO_ENABLED = 'yotpo/settings/active';
    const XML_PATH_YOTPO_APP_KEY = 'yotpo/settings/app_key';
    const XML_PATH_YOTPO_SECRET = 'yotpo/settings/secret';
    const XML_PATH_YOTPO_WIDGET_ENABLED = 'yotpo/settings/widget_enabled';
    const XML_PATH_YOTPO_YOTPO_CATEGORY_BOTTOMLINE_ENABLED = 'yotpo/settings/category_bottomline_enabled';
    const XML_PATH_YOTPO_BOTTOMLINE_ENABLED = 'yotpo/settings/bottomline_enabled';
    const XML_PATH_YOTPO_BOTTOMLINE_QNA_ENABLED = 'yotpo/settings/qna_enabled';
    const XML_PATH_YOTPO_MDR_ENABLED = 'yotpo/settings/mdr_enabled';
    const XML_PATH_YOTPO_DEBUG_MODE_ENABLED = 'yotpo/settings/debug_mode_active';
    const XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE = 'yotpo/sync_settings/orders_sync_start_date';
    const XML_PATH_YOTPO_CUSTOM_ORDER_STATUS = 'yotpo/settings/custom_order_status';
    const XML_PATH_YOTPO_ORDERS_SYNC_LIMIT = 'yotpo/sync_settings/orders_sync_limit';
    //= Not visible on system.xml
    const XML_PATH_YOTPO_API_URL = 'yotpo/env/yotpo_api_url';
    const XML_PATH_YOTPO_WIDGET_URL = 'yotpo/env/yotpo_widget_url';
    const XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE = 'yotpo/module_info/yotpo_installation_date';

    private $allStoreIds = [0 => null, 1 => null];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var DateTimeFactory
     */
    private $datetimeFactory;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @method __construct
     * @param  StoreManagerInterface    $storeManager
     * @param  ScopeConfigInterface     $scopeConfig
     * @param  ResourceConfig           $resourceConfig
     * @param  EncryptorInterface       $encryptor
     * @param  DateTimeFactory          $datetimeFactory
     * @param  ModuleListInterface      $moduleList
     * @param  ProductMetadataInterface $productMetadata
     * @param  LoggerInterface          $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
        EncryptorInterface $encryptor,
        DateTimeFactory $datetimeFactory,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->datetimeFactory = $datetimeFactory;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->logger = $logger;
    }

    /**
     * @method getStoreManager
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @method isSingleStoreMode
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * @method getWebsiteIdByStoreId
     * @param int $storeId
     * @return int
     */
    public function getWebsiteIdByStoreId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null)
    {
        if (!$scope && $this->isSingleStoreMode()) {
            return $this->scopeConfig->getValue($configPath);
        }
        $scopeId = ($scopeId === null) ? $this->getCurrentStoreId() : $scopeId;

        return $this->scopeConfig->getValue($configPath, $scope ?: ScopeInterface::SCOPE_STORE, $scopeId);
    }

    /**
     * @return boolean
     */
    public function isEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isDebugMode($scope = null, $scopeId = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $scope, $scopeId)) ? true : false;
    }

    /**
     * @return string
     */
    public function getAppKey($scopeId = null, $scope = null)
    {
        return $this->getConfig(self::XML_PATH_YOTPO_APP_KEY, $scopeId, $scope);
    }

    /**
     * @method getAppKeys
     * @param array $storeIds
     * @return array
     */
    public function getAppKeys(array $storeIds = [])
    {
        $appKeys = [];
        $storeIds = $storeIds ?: $this->getAllStoreIds(true);
        foreach ($storeIds as $storeId) {
            $appKeys[] = $this->getAppKey($storeId);
        }
        return array_unique(array_filter($appKeys));
    }

    /**
     * @return string
     */
    public function getSecret($scopeId = null, $scope = null)
    {
        return (($secret = $this->getConfig(self::XML_PATH_YOTPO_SECRET, $scopeId, $scope))) ? $this->encryptor->decrypt($secret) : null;
    }

    /**
     * @return boolean
     */
    public function isWidgetEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_WIDGET_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isCategoryBottomlineEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_YOTPO_CATEGORY_BOTTOMLINE_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isBottomlineEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_BOTTOMLINE_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isBottomlineQnaEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_BOTTOMLINE_QNA_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isMdrEnabled($scopeId = null, $scope = null)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_MDR_ENABLED, $scopeId, $scope)) ? true : false;
    }

    /**
     * @return array
     */
    public function getCustomOrderStatus($scopeId = null, $scope = null)
    {
        $orderStatuses = $this->getConfig(self::XML_PATH_YOTPO_CUSTOM_ORDER_STATUS, $scopeId, $scope);
        return ($orderStatuses) ? array_map('strtolower', explode(',', $orderStatuses)) : [Order::STATE_COMPLETE];
    }

    /**
     * @method getOrdersSyncAfterDate
     * @param  string                 $format
     * @return date
     */
    public function getOrdersSyncAfterDate($scopeId = null, $scope = null, $format = 'Y-m-d H:i:s')
    {
        $timestamp = strtotime($this->getConfig(self::XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE, $scopeId, $scope) ?: $this->getCurrentDate());
        return date($format, $timestamp);
    }

    /**
     * @return int
     */
    public function getOrdersSyncLimit()
    {
        return (($limit = (int)$this->getConfig(self::XML_PATH_YOTPO_ORDERS_SYNC_LIMIT)) > 0) ? $limit : 0;
    }

    /**
     * @method getModuleInstallationDate
     * @param  string                 $format
     * @return date
     */
    public function getModuleInstallationDate($format = 'Y-m-d')
    {
        $timestamp = strtotime($this->getConfig(self::XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE) ?: $this->getCurrentDate());
        return date($format, $timestamp);
    }

    /**
     * @method getOrdersSyncAfterMinDate
     * @param  string                 $format
     * @return date
     */
    public function getOrdersSyncAfterMinDate($format = 'Y-m-d')
    {
        $timestamp = strtotime("-3 months", strtotime($this->getConfig(self::XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE) ?: $this->getCurrentDate()));
        return date($format, $timestamp);
    }

    /**
     * @return boolean
     */
    public function isAppKeyAndSecretSet($scopeId = null, $scope = null)
    {
        return ($this->getAppKey($scopeId, $scope) && $this->getSecret($scopeId, $scope)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isActivated($scopeId = null, $scope = null)
    {
        return ($this->isEnabled($scopeId, $scope) && $this->isAppKeyAndSecretSet($scopeId, $scope)) ? true : false;
    }

    /**
     * @method setStoreCredentialsAndIsEnabled
     * @param  string|null                     $appKey
     * @param  string|null                     $secret
     * @param  boolean|int|null                $isEnabled
     * @param  int|null                        $storeId
     * @param  string|null                     $scopes
     * @return $this
     */
    public function setStoreCredentialsAndIsEnabled($appKey, $secret, $isEnabled, $storeId = null, $scopes = ScopeInterface::SCOPE_STORES)
    {
        $storeId = ($storeId === null) ? $this->getCurrentStoreId() : $storeId;
        
        $this->resourceConfig->saveConfig(self::XML_PATH_YOTPO_APP_KEY, $appKey, $scopes, $storeId);
        $this->resourceConfig->saveConfig(self::XML_PATH_YOTPO_SECRET, ($secret ? $this->encryptor->encrypt($secret) : null), $scopes, $storeId);
        $this->resourceConfig->saveConfig(self::XML_PATH_YOTPO_ENABLED, $isEnabled, $scopes, $storeId);
        return $this;
    }

    /**
     * @method resetStoreCredentials
     * @param  int|null              $storeId
     * @param  string|null           $scopes
     */
    public function resetStoreCredentials($storeId = null, $scopes = ScopeInterface::SCOPE_STORES)
    {
        $this->resourceConfig->deleteConfig(self::XML_PATH_YOTPO_ENABLED, $scopes, $storeId);
        $this->resourceConfig->deleteConfig(self::XML_PATH_YOTPO_APP_KEY, $scopes, $storeId);
        $this->resourceConfig->deleteConfig(self::XML_PATH_YOTPO_SECRET, $scopes, $storeId);
        return $this;
    }

    /**
     * @method getYotpoApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoApiUrl($path = "")
    {
        $yotpoApiUrl = $this->getConfig(self::XML_PATH_YOTPO_API_URL);
        if(preg_match("/yotpo\.com\/$|yotpo\.xyz\/$/", $yotpoApiUrl)) {
        return $yotpoApiUrl . $path;
        } else {
            return "https://api.yotpo.com/" . $path;
        }
    }

    /**
     * @method getYotpoNoSchemaApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoNoSchemaApiUrl($path = "")
    {
        return preg_replace('#^https?:#', '', $this->getYotpoApiUrl($path));
    }

    /**
     * @method getYotpoWidgetUrl
     * @return string
     */
    public function getYotpoWidgetUrl()
    {
        return $this->getConfig(self::XML_PATH_YOTPO_WIDGET_URL) . $this->getAppKey() . '/widget.js';
    }

    /**
     * Log to system.log
     * @method log
     * @param  mixed  $message
     * @param  string $type
     * @param  array  $data
     * @return $this
     */
    public function log($message, $type = "debug", $data = [], $prefix = '[Yotpo Log] ')
    {
        if ($type !== 'debug' || $this->isDebugMode()) {
            if (!isset($data['store_id'])) {
                $data['store_id'] = $this->getCurrentStoreId();
            }
            if (!isset($data['app_key'])) {
                $data['app_key'] = $this->getAppKey();
            }
            switch ($type) {
                case 'error':
                    $this->logger->error($prefix . json_encode($message), $data);
                    break;
                case 'info':
                    $this->logger->info($prefix . json_encode($message), $data);
                    break;
                case 'debug':
                default:
                    $this->logger->debug($prefix . json_encode($message), $data);
                    break;
            }
        }
        return $this;
    }

    /**
     * @method getCurrentDate
     * @return date
     */
    public function getCurrentDate()
    {
        return $this->datetimeFactory->create()->gmtDate();
    }

    /**
     * @method getMediaUrl
     * @param  string $mediaPath
     * @param  string $filePath
     * @return string
     */
    public function getMediaUrl($mediaPath, $filePath)
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . trim($mediaPath, "/") . "/" . ltrim($filePath, "/");
    }

    /**
     * @method getCurrentStoreId
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @method getAllStoreIds
     * @param  boolean $withDefault
     * @param  boolean $onlyActive
     * @return array
     */
    public function getAllStoreIds($withDefault = false, $onlyActive = true)
    {
        $cacheKey = ($withDefault) ? 1 : 0;
        if ($this->allStoreIds[$cacheKey] === null) {
            $this->allStoreIds[$cacheKey] = [];
            foreach ($this->storeManager->getStores($withDefault) as $store) {
                if ($onlyActive && !$store->isActive()) {
                    continue;
                }
                $this->allStoreIds[$cacheKey][] = $store->getId();
            }
        }
        return $this->allStoreIds[$cacheKey];
    }

    /**
     * @method filterDisabledStoreIds
     * @param  array $storeIds
     * @return array
     */
    public function filterDisabledStoreIds(array $storeIds = [])
    {
        foreach ($storeIds as $key => $storeId) {
            if (!($this->isEnabled($storeId, ScopeInterface::SCOPE_STORE) && $this->isAppKeyAndSecretSet($storeId, ScopeInterface::SCOPE_STORE))) {
                unset($storeIds[$key]);
            }
        }
        return array_values($storeIds);
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getMagentoPlatformName()
    {
        return $this->productMetadata->getName();
    }

    public function getMagentoPlatformEdition()
    {
        return $this->productMetadata->getEdition();
    }

    public function getMagentoPlatformVersion()
    {
        return $this->productMetadata->getVersion();
    }
}
