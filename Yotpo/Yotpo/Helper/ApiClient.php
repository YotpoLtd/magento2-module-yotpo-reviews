<?php

namespace Yotpo\Yotpo\Helper;

class ApiClient 
{

  const YOTPO_OAUTH_TOKEN_URL   = "https://api.yotpo.com/oauth/token";
  const YOTPO_SECURED_API_URL   = "https://api.yotpo.com";
  const YOTPO_UNSECURED_API_URL = "http://api.yotpo.com";
  const DEFAULT_TIMEOUT = 30;
 
  public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager, 
                              \Magento\ConfigurableProduct\Model\Resource\Product\Type\Configurable $bundleSelection,
                              \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
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
    $this->_app_key = $config->getAppKey();
    $this->_secret = $config->getSecret();
    $this->_logger = $logger;
    $this->_imgHelper = $imgHelper;
  }

  public function prepareProductsData($order) 
  {
    $this->_storeManager->setCurrentStore($order->getStoreId());
    $products = $order->getAllVisibleItems(); //filter out simple products
    $products_arr = array();

    foreach ($products as $item) {
      $full_product = $this->_productRepository->get($item->getSku()); 
      $parentIds= $this->_bundleSelection->getParentIdsByChild($full_product->getId());
      if (count($parentIds) > 0) {
              $full_product = $this->_productRepository->getById($parentIds[0]); 
      }
      $product_data = array();
      $product_data['name'] = $full_product->getName();
      $product_data['url'] = '';
      $product_data['image'] = '';
      try 
      {
        $product_data['url'] = $full_product->getUrlInStore(array('_store' => $order->getStoreId()));
        $product_data['image'] = (string) $this->_imgHelper->init($full_product, 'image'); 
      } catch(Exception $e) { }
      $product_data['description'] = $this->_escaper->escapeHtml(strip_tags($full_product->getDescription()));
      $product_data['price'] = $item->getPrice();
      $products_arr[$full_product->getId()] = $product_data;
      }
      return $products_arr;
    }


  public function oauthAuthentication()
  {
    if($this->_app_key == null|| $this->_secret == null) {
      $this->_logger->addDebug('Missing app key or secret');
      return null;
    }
    $yotpo_options = array('client_id' => $this->_app_key, 'client_secret' => $this->_secret, 'grant_type' => 'client_credentials');
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
    catch(Exception $e) 
    {
      $this->_logger->addDebug('error: ' .$e); 
      return null;
    }
  }

  public function createApiPost($path, $data, $timeout=self::DEFAULT_TIMEOUT) {
    try 
    {
      $cfg = array('timeout' => $timeout);
      $http = $this->_curlFactory->create();
      $feed_url = self::YOTPO_SECURED_API_URL."/".$path;
      $http->setConfig($cfg);
      $http->write(\Zend_Http_Client::POST, $feed_url, '1.1', array('Content-Type: application/json'), json_encode($data));
      $resData = $http->read();
      $formattedRes = array("code" => \Zend_Http_Response::extractCode($resData), "body" => json_decode(\Zend_Http_Response::extractBody($resData), true));
      return $formattedRes;
    }
    catch(Exception $e)
    {
      $this->_logger->addDebug('error: ' .$e); 
    } 
  }

  public function createPurchases($order)
  {
    $this->createApiPost("apps/".$this->_app_key."/purchases", $order);
  }
  public function massCreatePurchases($orders, $token)
  {
    $data = array();
    $data['utoken'] = $token;
    $data['platform'] = 'magento';
    $data['orders'] = $orders;
    $this->createApiPost("apps/".$this->_app_key."/purchases/mass_create", $data);
  }

  // public function createApiGet($path, $timeout=self::DEFAULT_TIMEOUT)  //TODO  -  not sure if needed

}