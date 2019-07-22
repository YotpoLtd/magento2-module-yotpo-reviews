<?php

namespace Yotpo\Yotpo\Model;

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
    const XML_PATH_YOTPO_ALL = "yotpo";
    const XML_PATH_YOTPO_ENABLED = "yotpo/settings/active";
    const XML_PATH_YOTPO_APP_KEY = 'yotpo/settings/app_key';
    const XML_PATH_YOTPO_SECRET = 'yotpo/settings/secret';
    const XML_PATH_YOTPO_WIDGET_ENABLED = 'yotpo/settings/widget_enabled';
    const XML_PATH_YOTPO_YOTPO_CATEGORY_BOTTOMLINE_ENABLED = 'yotpo/settings/category_bottomline_enabled';
    const XML_PATH_YOTPO_BOTTOMLINE_ENABLED = 'yotpo/settings/bottomline_enabled';
    const XML_PATH_YOTPO_BOTTOMLINE_QNA_ENABLED = 'yotpo/settings/qna_enabled';
    const XML_PATH_YOTPO_MDR_ENABLED = 'yotpo/settings/mdr_enabled';
    const XML_PATH_YOTPO_DEBUG_MODE_ENABLED = "yotpo/settings/debug_mode_active";
    const XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE = "yotpo/sync_settings/orders_sync_start_date";
    const XML_PATH_YOTPO_CUSTOM_ORDER_STATUS = 'yotpo/settings/custom_order_status';
    const XML_PATH_YOTPO_ORDERS_SYNC_LIMIT = "yotpo/sync_settings/orders_sync_limit";

    const XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE = "yotpo/module_info/yotpo_installation_date"; //Not visible on system.xml

    private $yotpoSecuredApiUrl = 'https://api.yotpo.com/';
    private $yotpoUnsecuredApiUrl = 'http://api.yotpo.com/';
    private $yotpoWidgetUrl = '//staticw2.yotpo.com/';
    private $initializedUrls;

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
     * @param  EncryptorInterface       $encryptor
     * @param  DateTimeFactory          $datetimeFactory
     * @param  ModuleListInterface      $moduleList
     * @param  ProductMetadataInterface $productMetadata
     * @param  LoggerInterface          $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        DateTimeFactory $datetimeFactory,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->datetimeFactory = $datetimeFactory;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->logger = $logger;
    }

    private function initializeUrls()
    {
        if (!$this->initializedUrls) {
            if (($testEnvApi = rtrim(getenv("TEST_ENV_API"), "/"))) {
                $this->yotpoSecuredApiUrl = $testEnvApi . "/";
                $this->yotpoUnsecuredApiUrl = $testEnvApi . "/";
            }

            if (($testEnvWidget = rtrim(getenv("TEST_ENV_WIDGET"), "/"))) {
                $this->yotpoWidgetUrl = $testEnvWidget . "/";
            }
        }
        return $this;
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
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null, $skipCahce = false)
    {
        $scope = ($scope === null) ? ScopeInterface::SCOPE_STORE : $scope;
        $scopeId = ($scopeId === null) ? $this->storeManager->getStore()->getId() : $scopeId;
        if ($skipCahce) {
            if ($scope === ScopeInterface::SCOPE_STORE) {
                $scope = ScopeInterface::SCOPE_STORES;
            } elseif ($scope === ScopeInterface::SCOPE_WEBSITE) {
                $scope = ScopeInterface::SCOPE_WEBSITES;
            }
            $collection = $this->_configCollectionFactory->create()
                ->addFieldToFilter('scope', $scope)
                ->addFieldToFilter('scope_id', $scopeId)
                ->addFieldToFilter('path', ['like' => $configPath . '%']);
            if ($collection->count()) {
                return $collection->getFirstItem()->getValue();
            }
        } else {
            return $this->scopeConfig->getValue($configPath, $scope, $scopeId);
        }
    }

    /**
     * @return array
     */
    public function getAllConfig($scopeId = null, $scope = null, $skipCahce = false)
    {
        return $this->getConfig(self::XML_PATH_YOTPO_ALL, $scopeId, $scope, $skipCahce);
    }

    /**
     * @return boolean
     */
    public function isEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isDebugMode($scope = null, $scopeId = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_DEBUG_MODE_ENABLED, $scope, $scopeId, $skipCahce)) ? true : false;
    }

    /**
     * @return string
     */
    public function getAppKey($scopeId = null, $scope = null, $skipCahce = false)
    {
        return $this->getConfig(self::XML_PATH_YOTPO_APP_KEY, $scopeId, $scope, $skipCahce);
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
    public function getSecret($scopeId = null, $scope = null, $skipCahce = false)
    {
        return (($secret = $this->getConfig(self::XML_PATH_YOTPO_SECRET, $scopeId, $scope, $skipCahce))) ? $this->encryptor->decrypt($secret) : null;
    }

    /**
     * @return boolean
     */
    public function isWidgetEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_WIDGET_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isCategoryBottomlineEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_YOTPO_CATEGORY_BOTTOMLINE_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isBottomlineEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_BOTTOMLINE_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isBottomlineQnaEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_BOTTOMLINE_QNA_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return boolean
     */
    public function isMdrEnabled($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getConfig(self::XML_PATH_YOTPO_MDR_ENABLED, $scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @return array
     */
    public function getCustomOrderStatus($scopeId = null, $scope = null, $skipCahce = false)
    {
        $orderStatuses = $this->getConfig(self::XML_PATH_YOTPO_CUSTOM_ORDER_STATUS, $scopeId, $scope, $skipCahce);
        return ($orderStatuses) ? array_map('strtolower', explode(',', $orderStatuses)) : [Order::STATE_COMPLETE];
    }

    /**
     * @method getOrdersSyncAfterDate
     * @param  string                 $format
     * @return date
     */
    public function getOrdersSyncAfterDate($scopeId = null, $scope = null, $format = 'Y-m-d H:i:s', $skipCahce = false)
    {
        $timestamp = strtotime($this->getConfig(self::XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE, $scopeId, $scope, $skipCahce) ?: $this->getCurrentDate());
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
    public function isAppKeyAndSecretSet($scopeId = null, $scope = null, $skipCahce = false)
    {
        return ($this->getAppKey($scopeId, $scope, $skipCahce) && $this->getSecret($scopeId, $scope, $skipCahce)) ? true : false;
    }

    /**
     * @method getYotpoNoSchemaApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoNoSchemaApiUrl($path = "")
    {
        $this->initializeUrls();
        return preg_replace('#^https?:#', '', $this->yotpoSecuredApiUrl) . $path;
    }

    /**
     * @method getYotpoSecuredApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoSecuredApiUrl($path = "")
    {
        $this->initializeUrls();
        return $this->yotpoSecuredApiUrl . $path;
    }

    /**
     * @method getYotpoUnsecuredApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoUnsecuredApiUrl($path = "")
    {
        $this->initializeUrls();
        return $this->yotpoUnsecuredApiUrl . $path;
    }

    /**
     * @method getYotpoWidgetUrl
     * @return string
     */
    public function getYotpoWidgetUrl()
    {
        $this->initializeUrls();
        return $this->yotpoWidgetUrl . $this->getAppKey() . '/widget.js';
    }

    /**
     * @method log
     * @param  mixed  $message
     * @param  string $type
     * @param  array  $data
     * @return $this
     */
    public function log($message, $type = "info", $data = [], $prefix = '[Yotpo Log] ')
    {
        if ($this->isDebugMode()) { //Log to system.log
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
                case 'debug':
                    //$this->logger->debug($prefix . json_encode($message), $data);
                    //break;
                default:
                    $this->logger->info($prefix . json_encode($message), $data);
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
     * @return array
     */
    public function getAllStoreIds($withDefault = false)
    {
        $cacheKey = ($withDefault) ? 1 : 0;
        if ($this->allStoreIds[$cacheKey] === null) {
            $this->allStoreIds[$cacheKey] = [];
            foreach ($this->storeManager->getStores($withDefault) as $store) {
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
