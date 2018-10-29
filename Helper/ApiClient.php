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
    $conProduct = $objectManager->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable');
    $bendledProduct = $objectManager->create('\Magento\Bundle\Model\Product\Type');
    $productModel = $objectManager->create('\Magento\Catalog\Model\Product');
    $productCollection = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
    $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
    $products = $order->getAllItems();
    $products_arr = array();
    foreach ($products as $item) {
        $parentId = $item->getProduct()->getId();
        if ($item->getData('product_type') === 'simple' || $item->getData('product_type') === 'grouped') {
            $configurableProduct = $conProduct->getParentIdsByChild($item->getProduct()->getId());
            $bundleProduct = $bendledProduct->getParentIdsByChild($item->getProduct()->getId());
            if ($configurableProduct) {
                $parentProduct = $productModel->load($parentId);
                $productDetails = $productCollection->create()->addAttributeToSelect('*')
                        ->addStoreFilter()
                        ->addFieldToFilter('entity_id', ['in' => $configurableProduct[0]]);
                foreach ($productDetails as $pdetail) {
                    $productName = $pdetail->getName();
                    $productUrl = $pdetail->getProductUrl();
                    $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $pdetail->getImage();
                    $sku = $pdetail->getSku();
                    $upc = $pdetail->getUpc();
                    $isbn = $pdetail->getIsbn();
					$mpn = $pdetail->getMpn();
                    $brand = $pdetail->getBrand();
                }
            } elseif ($bundleProduct) {
                $parentProduct = $productModel->load($parentId);
                $productDetails = $productCollection->create()->addAttributeToSelect('*')
                        ->addStoreFilter()
                        ->addFieldToFilter('entity_id', ['in' => $bundleProduct[0]]);
                foreach ($productDetails as $pdetail) {
                    $productName = $pdetail->getName();
                    $productUrl = $pdetail->getProductUrl();
                    $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $pdetail->getImage();
                    $sku = $pdetail->getSku();
                    $upc = $pdetail->getUpc();
                    $isbn = $pdetail->getIsbn();
                    $mpn = $pdetail->getMpn();
                    $brand = $pdetail->getBrand();
                }
                $configurableProduct = 0;
                $bundleProduct = 0;
            }
			$specs_data = array();
            $product_data = array();
            $product_data['name'] = $productName;
            $product_data['url'] = '';
            $product_data['image'] = '';
            try {
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
                $rawdescription = str_replace(array('\'', '"'), '', $parentProduct->getDescription());
                $description = $this->_escaper->escapeHtml(strip_tags($rawdescription));
                $product_data['description'] = $description;
                $product_data['price'] = $parentProduct->getPrice();
                $products_arr[$parentProduct->getId()] = $product_data;
            }
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
