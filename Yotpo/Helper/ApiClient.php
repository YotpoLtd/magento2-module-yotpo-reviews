<?php

namespace Yotpo\Yotpo\Helper;

class ApiClient 
{

  const YOTPO_OAUTH_TOKEN_URL   = "https://api.yotpo.com/oauth/token";
  const YOTPO_SECURED_API_URL   = "https://api.yotpo.com";
  const YOTPO_UNSECURED_API_URL = "http://api.yotpo.com";
  const DEFAULT_TIMEOUT = 30;
  
  protected $disable_feature = null;
  protected $app_keys = array();
  protected $secrets = array();  

  public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager, 
                              \Magento\Bundle\Model\Resource\Selection $bundleSelection,
                              \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
                              \Magento\Framework\Escaper $escaper,
                              \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory) 
  {
    $this->storeManager = $storeManager;
    $this->bundleSelection = $bundleSelection;  
    $this->productRepository = $productRepository;     
    $this->escaper = $escaper;
    $this->curlFactory = $curlFactory;
  }

  public function prepareProductsData($order) 
  {
    $this->storeManager->setCurrentStore($order->getStoreId());
    $products = $order->getAllVisibleItems(); //filter out simple products
    $products_arr = array();

    foreach ($products as $item) {
      $full_product = $this->productRepository->get($item->getSku()); 
      $parentIds= $this->bundleSelection->getParentIdsByChild($item->getProductId());
      if (count($parentIds) > 0) {
              $full_product = $this->productRepository->get($parentIds[0]); //TODO: needs testing
      }
      $product_data = array();
      $product_data['name'] = $full_product->getName();
      $product_data['url'] = '';
      $product_data['image'] = '';
      try 
      {
        $product_data['url'] = $full_product->getUrlInStore(array('_store' => $order->getStoreId()));
        $product_data['image'] = $full_product->getImageUrl();  
      } catch(Exception $e) { }
      $product_data['description'] = $this->escaper->escapeHtml(strip_tags($full_product->getDescription()));
      $product_data['price'] = $item->getPrice();
      $products_arr[$full_product->getId()] = $product_data;
      }
      return $products_arr;
    }


  public function oauthAuthentication($store_id)
  {
    $this->app_keys[$store_id] = 'MzOL50pmJg6ZKoQWTY1IgxYlK8EkCWzZ9wxi6XWq'; //TODO: mock data
    $this->secrets[$store_id] = 'uvSIGTmD1tQy9POOsRqQhDBiiyJvfAHcUfaLBl4R'; //TODO: mock data
    $store_app_key = $this->app_keys[$store_id];
    $store_secret = $this->secrets[$store_id];
    if ($store_app_key == null or $store_secret == null)
    {
      // Mage::log('Missing app key or secret');  //TODO: change to magento logging
      return null;
    }
    $yotpo_options = array('client_id' => $store_app_key, 'client_secret' => $store_secret, 'grant_type' => 'client_credentials');
    try 
    {
      $result = $this->createApiPost('oauth/token', $yotpo_options);
      return $result['body']->access_token;
    } 
    catch(Exception $e) 
    {
      // Mage::log('error: ' .$e); //TODO: change to magento logging
      return null;
    }
  }


  public function createApiPost($path, $data, $timeout=self::DEFAULT_TIMEOUT) {
    try 
    {
      $config = array('timeout' => $timeout);
      $http = $this->curlFactory->create();
      $feed_url = self::YOTPO_SECURED_API_URL."/".$path;
      $http->setConfig($config);
      $http->write(\Zend_Http_Client::POST, $feed_url, '1.1', array('Content-Type: application/json'), json_encode($data));
      $resData = $http->read();
      return array("code" => \Zend_Http_Response::extractCode($resData), "body" => json_decode(\Zend_Http_Response::extractBody($resData)));
    }
    catch(Exception $e)
    {
      // Mage::log('error: ' .$e); //TODO: change to magento logging
    } 
  }

  public function createPurchases($order, $store_id)
  {
    $this->createApiPost("apps/".$this->app_keys[$store_id]."/purchases", $order);
  }
  public function massCreatePurchases($orders, $token, $store_id)
  {
    $data = array();
    $data['utoken'] = $token;
    $data['platform'] = 'magento';
    $data['orders'] = $orders;
    $this->createApiPost("apps/".$this->app_keys[$store_id]."/purchases/mass_create", $data);
  }

  // public function createApiGet($path, $timeout=self::DEFAULT_TIMEOUT)  //TODO  -  not sure if needed

}