<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
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

    protected $_yotpo_secured_api_url = 'https://api.yotpo.com/';
    protected $_yotpo_unsecured_api_url = 'http://api.yotpo.com/';
    protected $_yotpo_widget_url = '//staticw2.yotpo.com/';
    protected $_allStoreIds = [0 => null, 1 => null];

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var array
     */
    protected $_orderStatuses = [];

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var DateTimeFactory
     */
    protected $_datetimeFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CatalogImageHelper
     */
    protected $_catalogImageHelper;

    /**
     * @var AppEmulation
     */
    protected $_appEmulation;

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @method __construct
     * @param  Context                  $context
     * @param  StoreManagerInterface    $storeManager
     * @param  EncryptorInterface       $encryptor
     * @param  Escaper                  $escaper
     * @param  DateTimeFactory          $datetimeFactory
     * @param  Registry                 $coreRegistry
     * @param  CatalogImageHelper       $catalogImageHelper
     * @param  AppEmulation             $appEmulation
     * @param  ModuleListInterface      $moduleList
     * @param  ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        Escaper $escaper,
        DateTimeFactory $datetimeFactory,
        Registry $coreRegistry,
        CatalogImageHelper $catalogImageHelper,
        AppEmulation $appEmulation,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata
    ) {
        $this->_context = $context;
        $this->_storeManager = $storeManager;
        $this->_encryptor = $encryptor;
        $this->_escaper = $escaper;
        $this->_datetimeFactory = $datetimeFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_catalogImageHelper = $catalogImageHelper;
        $this->_appEmulation = $appEmulation;
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
        $this->_logger = $context->getLogger();
        parent::__construct($context);

        if (($testEnvApi = rtrim(getenv("TEST_ENV_API"), "/"))) {
            $this->_yotpo_secured_api_url = $testEnvApi . "/";
            $this->_yotpo_unsecured_api_url = $testEnvApi . "/";
        }

        if (($testEnvWidget = rtrim(getenv("TEST_ENV_WIDGET"), "/"))) {
            $this->_yotpo_widget_url = $testEnvWidget . "/";
        }
    }

    ///////////////////////////
    // Constructor Instances //
    ///////////////////////////

    /**
     * @method getStoreManager
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * @method getEncryptor
     * @return EncryptorInterface
     */
    public function getEncryptor()
    {
        return $this->_encryptor;
    }

    /**
     * @method getEscaper
     * @return Escaper
     */
    public function getEscaper()
    {
        return $this->_escaper;
    }

    /**
     * @method getDatetimeFactory
     * @return DateTimeFactory
     */
    public function getDatetimeFactory()
    {
        return $this->_datetimeFactory;
    }

    /**
     * @method getCoreRegistry
     * @return Registry
     */
    public function getCoreRegistry()
    {
        return $this->_coreRegistry;
    }

    /**
     * @method getCatalogImageHelper
     * @return CatalogImageHelper
     */
    public function getCatalogImageHelper()
    {
        return $this->_catalogImageHelper;
    }

    /**
     * @method getAppEmulation
     * @return AppEmulation
     */
    public function getAppEmulation()
    {
        return $this->_appEmulation;
    }

    /**
     * @method getLogger
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    ////////////
    // Config //
    ////////////

    /**
     * @return mixed
     */
    public function getConfig($configPath, $scopeId = null, $scope = null, $skipCahce = false)
    {
        $scope = ($scope === null) ? ScopeInterface::SCOPE_STORE : $scope;
        $scopeId = ($scopeId === null) ? $this->getStoreManager()->getStore()->getId() : $scopeId;
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
        return (($secret = $this->getConfig(self::XML_PATH_YOTPO_SECRET, $scopeId, $scope, $skipCahce))) ? $this->_encryptor->decrypt($secret) : null;
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
        if (!$this->_orderStatuses) {
            $this->_orderStatuses = $this->getConfig(self::XML_PATH_YOTPO_CUSTOM_ORDER_STATUS, $scopeId, $scope, $skipCahce);
            if (!$this->_orderStatuses) {
                $this->_orderStatuses = [Order::STATE_COMPLETE];
            } else {
                $this->_orderStatuses = array_map('strtolower', explode(',', $this->_orderStatuses));
            }
        }
        return $this->_orderStatuses;
    }

    /**
     * @method getOrdersSyncAfterDate
     * @param  string                 $format
     * @return date
     */
    public function getOrdersSyncAfterDate($format = 'Y-m-d H:i:s')
    {
        $timestamp = strtotime($this->getConfig(self::XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE) ?: $this->getCurrentDate());
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
        return preg_replace('#^https?:#', '', $this->_yotpo_secured_api_url) . $path;
    }

    /**
     * @method getYotpoSecuredApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoSecuredApiUrl($path = "")
    {
        return $this->_yotpo_secured_api_url . $path;
    }

    /**
     * @method getYotpoUnsecuredApiUrl
     * @param  string $path
     * @return string
     */
    public function getYotpoUnsecuredApiUrl($path = "")
    {
        return $this->_yotpo_unsecured_api_url . $path;
    }

    /**
     * @method getYotpoWidgetUrl
     * @return string
     */
    public function getYotpoWidgetUrl()
    {
        return $this->_yotpo_widget_url . $this->getAppKey() . '/widget.js';
    }

    ///////////////////////////////
    // App Environment Emulation //
    ///////////////////////////////

    /**
     * Start environment emulation of the specified store
     *
     * Function returns information about initial store environment and emulates environment of another store
     *
     * @param  integer $storeId
     * @param  string  $area
     * @param  bool    $force   A true value will ensure that environment is always emulated, regardless of current store
     * @return \Yotpo\Yotpo\Helper\Data
     */
    public function startEnvironmentEmulation($storeId, $area = Area::AREA_FRONTEND, $force = false)
    {
        $this->getAppEmulation()->startEnvironmentEmulation($storeId, $area, $force);
        return $this;
    }

    /**
     * Stop environment emulation
     *
     * Function restores initial store environment
     *
     * @return \Yotpo\Yotpo\Helper\Data
     */
    public function stopEnvironmentEmulation()
    {
        $this->getAppEmulation()->stopEnvironmentEmulation();
        return $this;
    }

    public function emulateFrontendArea($storeId, $force = false)
    {
        $this->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, $force);
        return $this;
    }

    public function emulateAdminArea($storeId, $force = false)
    {
        $this->startEnvironmentEmulation($storeId, Area::AREA_ADMINHTML, $force);
        return $this;
    }

    ///////////////
    // Renderers //
    ///////////////

    public function showWidget(AbstractBlock $parentBlock, Product $product = null)
    {
        return $this->renderYotpoProductBlock('widget_div', $parentBlock, $product);
    }

    public function showBottomline(AbstractBlock $parentBlock, Product $product = null)
    {
        return $this->renderYotpoProductBlock('bottomline', $parentBlock, $product);
    }

    protected function renderYotpoProductBlock($blockName, AbstractBlock $parentBlock, Product $product = null)
    {
        return $parentBlock->getLayout()->createBlock('Yotpo\Yotpo\Block\Yotpo')
          ->setTemplate('Yotpo_Yotpo::' . $blockName . '.phtml')
          ->setAttribute('product', $product)
          ->setAttribute('fromHelper', true)
          ->toHtml();
    }

    public function getCategoryBottomLineHtml(Product $product)
    {
        return '<div class="yotpo bottomLine bottomline-position" data-product-id="' . $product->getId() . '" data-url="' . $product->getProductUrl() . '"></div>';
    }

    ////////////
    // Utils //
    ///////////

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
                $this->_logger->error($prefix . json_encode($message), $data);
                break;
            case 'debug':
                //$this->_logger->debug($prefix . json_encode($message), $data);
                //break;
            default:
                $this->_logger->info($prefix . json_encode($message), $data);
                break;
            }
        }
        return $this;
    }

    /**
     * @method escapeHtml
     * @param  string $str
     * @return string
     */
    public function escapeHtml($str)
    {
        return $this->_escaper->escapeHtml($str);
    }

    /**
     * @method getCurrentDate
     * @return date
     */
    public function getCurrentDate()
    {
        return $this->getDatetimeFactory()->create()->gmtDate();
    }

    /**
     * @method strToCamelCase
     * @param  string         $str
     * @param  string         $prefix
     * @param  string         $suffix
     * @return string
     */
    public function strToCamelCase($str, $prefix = '', $suffix = '')
    {
        return $prefix . str_replace('_', '', ucwords($str, '_')) . $suffix;
    }

    /**
     * @method getMediaUrl
     * @param  string $mediaPath
     * @param  string $filePath
     * @return string
     */
    public function getMediaUrl($mediaPath, $filePath)
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . trim($mediaPath, "/") . "/" . ltrim($filePath, "/");
    }

    /**
     * @method getProductMainImageUrl
     * @param  Product $product
     * @return string
     */
    public function getProductMainImageUrl(Product $product)
    {
        if (($filePath = $product->getData("image"))) {
            return (string) $this->getMediaUrl("catalog/product", $filePath);
        }
        return "";
    }

    /**
     * @method getProductImageUrl
     * @param  Product $product
     * @param  string  $imageId
     * @return string
     */
    public function getProductImageUrl(Product $product, $imageId = 'product_page_image_large')
    {
        return $this->_catalogImageHelper->init($product, $imageId)->getUrl();
    }

    public function getCurrentProduct()
    {
        if ($this->_product === null) {
            $this->_product = $this->_coreRegistry->registry('current_product');
            if (!$this->_product) {
                $this->_product = false;
            }
        }
        return $this->_product;
    }

    /**
     * @method getCurrentStoreId
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @method getAllStoreIds
     * @param  boolean $withDefault
     * @return array
     */
    public function getAllStoreIds($withDefault = false)
    {
        $cacheKey = ($withDefault) ? 1 : 0;
        if ($this->_allStoreIds[$cacheKey] === null) {
            $this->_allStoreIds[$cacheKey] = [];
            foreach ($this->_storeManager->getStores($withDefault) as $store) {
                $this->_allStoreIds[$cacheKey][] = $store->getId();
            }
        }
        return $this->_allStoreIds[$cacheKey];
    }

    public function getModuleVersion()
    {
        return $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getMagentoPlatformName()
    {
        return $this->_productMetadata->getName();
    }

    public function getMagentoPlatformEdition()
    {
        return $this->_productMetadata->getEdition();
    }

    public function getMagentoPlatformVersion()
    {
        return $this->_productMetadata->getVersion();
    }
}
