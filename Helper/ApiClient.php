<?php

namespace Yotpo\Yotpo\Helper;

class ApiClient 
{

  const YOTPO_OAUTH_TOKEN_URL   = "https://api.yotpo.com/oauth/token";
  const YOTPO_SECURED_API_URL   = "https://api.yotpo.com";
  const YOTPO_UNSECURED_API_URL = "http://api.yotpo.com";
  const DEFAULT_TIMEOUT = 30;
 
  public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager, 
                              \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $bundleSelection,
                              \Magento\Catalog\Model\Product $productRepository,
                              \Magento\Framework\Escaper $escaper,
                              \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
                              \Yotpo\Yotpo\Block\Config $config,
                              \Psr\Log\LoggerInterface $logger,
                              \Magento\Catalog\Helper\Image $imgHelper) 
  {
    $this->_storeManager = $storeManager;
    $this->_bundleSelection = $bundleSelection;  
    $this->_productRepository = $productRepository;     
    $this->_escaper = $escaper;
    $this->_curlFactory = $curlFactory;
    $this->_logger = $logger;
    $this->_config = $config;
    $this->_imgHelper = $imgHelper;
  }

  public function prepareProductsData($order) 
  {
    $this->_storeManager->setCurrentStore($order->getStoreId());
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $groupedProductModel = $objectManager->create('\Magento\GroupedProduct\Model\Product\Type\Grouped');
    $productModel = $objectManager->create('\Magento\Catalog\Model\Product');
    $productCollection = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
    $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
    
    $products = $order->getAllVisibleItems();
    $products_arr = array();
    $product_data = array();
	$specs_data = array();
        $j = 0;
        foreach ($products as $item) {
            try {
                $productID = $item->getProduct()->getId();
                if ($item->getData('product_type') == 'simple') {
                    $_product = $productModel->load($productID);
                }
                if ($item->getData('product_type') == 'configurable' || $item->getData('product_type') == 'grouped' || $item->getData('product_type') == 'bundle') {
                    if ($item->getData('product_type') == 'grouped') {
                        $productIDs = $groupedProductModel->getParentIdsByChild($item->getProduct()->getId()) ? $groupedProductModel->getParentIdsByChild($item->getProduct()->getId()) : 0;
                        $productID = $productIDs[0];
                    }
                    if ($productCollection->create()->count()) {
                        $productCollection->create()->clear();
                        $_product = $item->getProduct();
                    }
                    $_products = $productCollection->create()->addAttributeToSelect('*')->addStoreFilter()->addFieldToFilter('entity_id', ['in' => $productID]);
                    foreach ($_products as $product) { // This is needed as we can't use object as array in collection way.
                        $_product = $product;

                        break 1;
                    }
                }

                $productName = $_product->getName();
                $productUrl = $_product->getProductUrl();
                $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
                $sku = $_product->getSku();
                $upc = $_product->getUpc();
                $isbn = $_product->getIsbn();
                $mpn = $_product->getMpn();
                $brand = $_product->getBrand();


                $product_data['name'] = $productName;
                $product_data['url'] = '';
                $product_data['image'] = '';
                $product_data['url'] = $productUrl;
                $product_data['image'] = $imageUrl;
                if ($upc) {
                    $specs_data['upc'] = $upc;
                }
                if ($isbn) {
                    $specs_data['isbn'] = $isbn;
                }
                if ($brand) {
                    $specs_data['brand'] = $brand;
                }
                if ($mpn) {
                    $specs_data['mpn'] = $mpn;
                }
                if ($sku) {
                    $specs_data['external_sku'] = $sku;
                }
                if (!empty($specs_data)) {
                    $product_data['specs'] = $specs_data;
                }
            } catch (\Exception $e) {
                $this->_logger->addDebug('ApiClient prepareProductsData Exception' . json_encode($e));
            }
            $rawdescription = str_replace(array('\'', '"'), '', $_product->getDescription());
            $description = $this->_escaper->escapeHtml(strip_tags($rawdescription));
            $product_data['description'] = $description;
            if ($item->getData('product_type') == 'grouped') {
                if (!isset($productPrice[$productID])) {
                    $productPrice[$productID] = 0.0000;
                }
                $productPrice[$productID] += $item->getData('row_total_incl_tax');
                $product_data['price'] = $productPrice[$productID];
            } else {
                if (!isset($productPrice[$productID])) {
                    $productPrice[$productID] = 0.0000;
                }
                $productPrice[$productID] += $item->getData('row_total_incl_tax');
                $product_data['price'] = $productPrice[$productID];
            }
            $products_arr[$productID] = $product_data;
        }
	return $products_arr;
  }

  public function oauthAuthentication($storeId)
  {
    $app_key = $this->_config->getAppKey($storeId);
    $secret = $this->_config->getSecret($storeId);
    if($app_key == null|| $secret == null) {
      $this->_logger->addDebug('Missing app key or secret');
      return null;
    }
    $yotpo_options = array('client_id' => $app_key, 'client_secret' => $secret, 'grant_type' => 'client_credentials');
    try 
    {
      $result = $this->createApiPost('oauth/token', $yotpo_options);
      if(!is_array($result))
      {
        $this->_logger->addDebug('error: no response from api'); 
        return null;
      }
      $valid_response = is_array($result['body']) && array_key_exists('access_token', $result['body']);
      if(!$valid_response)
      {
        $this->_logger->addDebug('error: no access token received'); 
        return null;
      }  
      return $result['body']['access_token']; 
    } 
    catch(\Exception $e) 
    {
      $this->_logger->addDebug('error: ' .$e); 
      return null;
    }
  }

  public function prepareOrderData($order) 
  {
    $data['email'] = $order->getCustomerEmail();
    $customer_name = $order->getCustomerFirstName().' '.$order->getCustomerLastName();
    if(trim($customer_name) ==''){
        $billing_address = $order->getBillingAddress();
		$customer_name = $billing_address->getFirstname().' '.$billing_address->getLastname();
    }
    $data['customer_name'] = $customer_name;
    $data['order_id'] = $order->getIncrementId();
    $data['platform'] = 'magento';
    $data['currency_iso'] = $order->getOrderCurrency()->getCode();
    $data['order_date'] = $order->getCreatedAt();        
    $data['products'] = $this->prepareProductsData($order); 
    return $data;
  }

  public function createApiPost($path, $data, $timeout=self::DEFAULT_TIMEOUT) {
    try 
    {
      $cfg = array('timeout' => $timeout);
      $http = $this->_curlFactory->create();
      $feed_url = self::YOTPO_SECURED_API_URL."/".$path;
      $http->setConfig($cfg);
      $http->write(\Zend_Http_Client::POST, $feed_url, '1.1', array('Content-Type: application/json'), json_encode($data));
      print_r(json_encode($data));
      $resData = $http->read();
      return array("code" => \Zend_Http_Response::extractCode($resData), "body" => json_decode(\Zend_Http_Response::extractBody($resData), true));
    }
    catch(\Exception $e)
    {
      $this->_logger->addDebug('error: ' .$e); 
    } 
  }

  public function createPurchases($order, $storeId)
  {
    $appKey = $this->_config->getAppKey($storeId);
    return $this->createApiPost("apps/".$appKey."/purchases", $order);
  }
  
  public function massCreatePurchases($orders, $token, $storeId)
  {
    $appKey = $this->_config->getAppKey($storeId);
    $data = array();
    $data['utoken'] = $token;
    $data['platform'] = 'magento';
    $data['orders'] = $orders;
    return $this->createApiPost("apps/".$appKey."/purchases/mass_create", $data);
  }
  public function createApiGet($path, $timeout = self::DEFAULT_TIMEOUT) {
        try {

            $cfg = array('timeout' => $timeout);
            $http = $this->_curlFactory->create();
            $feed_url = self::YOTPO_UNSECURED_API_URL . "/" . $path;
            $http->setConfig($cfg);
            $http->write(\Zend_Http_Client::GET, $feed_url, '1.1', array('Content-Type: application/json'));
            $resData = $http->read();
            return array("code" => \Zend_Http_Response::extractCode($resData), "body" => json_decode(\Zend_Http_Response::extractBody($resData)));
        } catch (\Exception $e) {
            $this->_logger->addDebug('error: ' . $e);
        }
    }
}
