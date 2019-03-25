<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Helper\Context;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductTypeGrouped;
use Magento\Sales\Model\Order;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;
use Yotpo\Yotpo\Lib\Http\Client\Curl;

class ApiClient extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_TIMEOUT = 30;
    const EXTENSION_VERSION = '2.7.5'

    /**
     * @var int
     */
    protected $_status;

    /**
     * @var array
     */
    protected $_headers;

    /**
     * @var array
     */
    protected $_body;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  Curl        $curl
     * @param  YotpoHelper $yotpoHelper
     * @param  ProductFactory $productFactory
     */
    public function __construct(
        Context $context,
        Curl $curl,
        YotpoHelper $yotpoHelper,
        ProductFactory $productFactory
    ) {
        $this->_curl = $curl;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_productFactory = $productFactory;
        parent::__construct($context);
    }

    /**
     * @return int
     */
    protected function getCurlStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->_curl->getStatus();
        }

        return $this->_status;
    }

    /**
     * @return array
     */
    protected function getCurlHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = $this->_curl->getHeaders();
        }

        return $this->_headers;
    }

    /**
     * @return array
     */
    protected function getCurlBody()
    {
        if ($this->_body === null) {
            $this->_body = json_decode($this->_curl->getBody());
        }

        return $this->_body;
    }

    /**
     * @return array
     */
    protected function prepareCurlResponseData()
    {
        return [
            'status' => $this->getCurlStatus(),
            'headers' => $this->getCurlHeaders(),
            'body' => $this->getCurlBody(),
        ];
    }

    protected function isOkResponse()
    {
        if ($this->getCurlStatus() === 200
            || ($this->getCurlStatus() === 100
            && is_array(($headers = $this->getCurlHeaders()))
            && isset($headers['Status'])
            && $headers['Status'] === '200 OK')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @method oauthAuthentication
     * @param  int|null $storeId
     * @return mixed
     */
    public function oauthAuthentication($storeId = null)
    {
        try {
            $app_key = $this->_yotpoHelper->getAppKey($storeId);
            $secret = $this->_yotpoHelper->getSecret($storeId);
            if (!($app_key && $secret)) {
                $this->_yotpoHelper->log("Missing app key or secret", "debug");
                return null;
            }
            $result = $this->sendApiRequest(
                'oauth/token',
                [
                'client_id' => $app_key,
                'client_secret' => $secret,
                'grant_type' => 'client_credentials'
                ]
            );
            if (!is_array($result)) {
                $this->_yotpoHelper->log("Yotpo ApiClient error: no response from api", "error");
                return null;
            }
            $token = (is_object($result['body']) && property_exists($result['body'], "access_token")) ? $result['body']->access_token : false;
            if (!$token) {
                $this->_yotpoHelper->log("Yotpo ApiClient error: no access token received", "error");
                return null;
            }
            return $token;
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo ApiClient oauthAuthentication Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
            return null;
        }
    }

    /**
     * @method prepareOrdersData
     * @param  \Magento\Sales\Model\ResourceModel\Order\Collection $ordersCollection
     * @return array
     */
    public function prepareOrdersData(\Magento\Sales\Model\ResourceModel\Order\Collection $ordersCollection)
    {
        $ordersData = [];

        try {
            foreach ($ordersCollection as $order) {
                $ordersData[] = $this->prepareOrderData($order);
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo ApiClient prepareOrdersData Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
            return [];
        }

        return array_filter($ordersData);
    }

    /**
     * @method prepareOrderData
     * @param  Order $order
     * @return array
     */
    public function prepareOrderData(Order $order)
    {
        $orderData = [];

        try {
            $orderData['products'] = $this->prepareProductsData($order);
            if (!$orderData['products']) {
                return [];
            }
            $orderData['order_id'] = $order->getIncrementId();
            $orderData['order_date'] = $order->getCreatedAt();
            $orderData['currency_iso'] = $order->getOrderCurrency()->getCode();
            $orderData['email'] = $order->getCustomerEmail();
            $orderData['customer_name'] = trim($order->getCustomerFirstName() . ' ' . $order->getCustomerLastName());
            if (!$orderData['customer_name'] && ($billingAddress = $order->getBillingAddress())) {
                $orderData['customer_name'] = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
            }
            if (!$order->getCustomerIsGuest()) {
                $orderData['user_reference'] = $order->getCustomerId();
            }
            $orderData['platform'] = 'magento';
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo ApiClient prepareOrderData Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
            return [];
        }

        return $orderData;
    }

    /**
     * @method prepareProductsData
     * @param  Order $order
     * @return array
     */
    protected function prepareProductsData(Order $order)
    {
        $productsData = [];
        $groupProductsParents = [];

        try {
            foreach ($order->getAllVisibleItems() as $orderItem) {
                try {
                    $product = null;
                    if ($orderItem->getProductType() === ProductTypeGrouped::TYPE_CODE) {
                        $productOptions = $orderItem->getProductOptions();
                        $productId = (isset($productOptions['super_product_config']) && isset($productOptions['super_product_config']['product_id'])) ? $productOptions['super_product_config']['product_id'] : null;
                        if ($productId) {
                            if (isset($groupProductsParents[$productId])) {
                                $product = $groupProductsParents[$productId];
                            } else {
                                $product = $groupProductsParents[$productId] = $this->_productFactory->create()->load($productId);
                            }
                        }
                    } else {
                        $product = $orderItem->getProduct();
                    }

                    if (!($product && $product->getId())) {
                        continue;
                    }
                    if ($orderItem->getProductType() === ProductTypeGrouped::TYPE_CODE && isset($productsData[$product->getId()])) {
                        $productsData[$product->getId()]['price'] += $orderItem->getData('row_total_incl_tax');
                    } else {
                        $productsData[$product->getId()] = [
                            'name'        => $product->getName(),
                            'url'         => $product->getProductUrl(),
                            'image'       => $this->_yotpoHelper->getProductMainImageUrl($product),
                            'description' => $this->_yotpoHelper->escapeHtml(strip_tags($product->getDescription())),
                            'price'       => $orderItem->getData('row_total_incl_tax'),
                            'specs'       => array_filter(
                                [
                                'external_sku' => $product->getSku(),
                                'upc'          => $product->getUpc(),
                                'isbn'         => $product->getIsbn(),
                                'mpn'          => $product->getMpn(),
                                'brand'        => $product->getBrand(),
                                ]
                            ),
                        ];
                    }
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("Yotpo ApiClient prepareProductsData Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo ApiClient prepareProductsData Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }

        return $productsData;
    }

    /**
     * @method sendApiRequest
     * @param  string $path
     * @param  array  $data
     * @param  string $method
     * @param  int    $timeout
     * @param  string $contentType
     * @return mixed
     */
    public function sendApiRequest($path, array $data, $method = "post", $timeout = self::DEFAULT_TIMEOUT, $contentType = 'application/json')
    {
        try {
            $this->_yotpoHelper->log("Yotpo ApiClient sendApiRequest - request: ", "info", [["path" => $path, "params" => $data, "method" => $method, "timeout" => $timeout, "contentType" => $contentType]]);

            $this->_curl->setHeaders(
                [
                'Content-Type' => $contentType
                ]
            );
            $this->_curl->setOption(CURLOPT_TIMEOUT, $timeout);

            call_user_func_array(
                [$this->_curl, strtolower($method)],
                [
                $this->_yotpoHelper->getYotpoSecuredApiUrl($path),
                $data
                ]
            );

            $this->_yotpoHelper->log("Yotpo ApiClient sendApiRequest - response: ", "info", $this->prepareCurlResponseData());
            return $this->prepareCurlResponseData();
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo ApiClient sendApiRequest Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
    }

    /**
     * @method createPurchases
     * @param  array  $order   Order prepared by $this->prepareOrderData()
     * @param  string $token
     * @param  int    $storeId
     * @return mixed
     */
    public function createPurchases(array $orderData, string $token, $storeId = null)
    {
        $orderData['utoken'] = $token;
        return $this->sendApiRequest("apps/" . $this->_yotpoHelper->getAppKey($storeId) . "/purchases", $orderData);
    }

    /**
     * @method massCreatePurchases
     * @param  array  $orders  Array of orders prepared by $this->prepareOrderData()
     * @param  string $token
     * @param  mixed  $storeId
     * @return mixed
     */
    public function massCreatePurchases(array $orders, string $token, $storeId = null)
    {
        return $this->sendApiRequest(
            "apps/" . $this->_yotpoHelper->getAppKey($storeId) . "/purchases/mass_create",
            [
            'utoken'   => $token,
            'platform' => 'magento2',
             extension_version => self::EXTENSION_VERSION,
            'orders'   => $orders,
            ]
        );
    }
}
