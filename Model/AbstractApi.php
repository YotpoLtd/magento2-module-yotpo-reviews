<?php

namespace Yotpo\Yotpo\Model;

use Magento\Catalog\Model\ProductFactory;
use Yotpo\Yotpo\Lib\Http\Client\Curl;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class AbstractApi
{
    const DEFAULT_TIMEOUT = 45;
    const NUMBER_OF_RETRY = 1;

    /**
     * @var int
     */
    private $status;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $body;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var YotpoConfig
     */
    protected $_yotpoConfig;

    /**
     * @var int
     */
    protected static $_retryCount = 0;

    /**
     * @var ResourceConfig
     */
    protected $resourceConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;


    /**
     * @method __construct
     * @param  Curl           $curl
     * @param  ProductFactory $productFactory
     * @param  YotpoConfig    $yotpoConfig
     * @param  ResourceConfig $resourceConfig
     * @param  EncryptorInterface $encryptor
     */
    public function __construct(
        Curl $curl,
        ProductFactory $productFactory,
        YotpoConfig $yotpoConfig,
        ResourceConfig $resourceConfig,
        EncryptorInterface $encryptor
    ) {
        $this->curl = $curl;
        $this->productFactory = $productFactory;
        $this->_yotpoConfig = $yotpoConfig;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @param bool $refresh
     * @return int
     */
    protected function _getCurlStatus($refresh = false)
    {
        if ($this->status === null || $refresh) {
            $this->status = $this->curl->getStatus();
        }

        return $this->status;
    }

    /**
     * @param bool $refresh
     * @return array
     */
    protected function _getCurlHeaders($refresh = false)
    {
        if ($this->headers === null || $refresh) {
            $this->headers = $this->curl->getHeaders();
        }

        return $this->headers;
    }

    /**
     * @param bool $refresh
     * @return array
     */
    protected function _getCurlBody($refresh = false)
    {
        if ($this->body === null || $refresh) {
            $this->body = json_decode($this->curl->getBody());
        }

        return $this->body;
    }

    /**
     * @return $this
     */
    protected function _clearResponseData()
    {
        $this->body = $this->status = $this->headers = null;
        return $this;
    }

    /**
     * @return array
     */
    protected function _prepareCurlResponseData()
    {
        $responseData = [
            'status' => $this->_getCurlStatus(),
            'headers' => $this->_getCurlHeaders(),
            'body' => $this->_getCurlBody(),
        ];
        return $responseData;
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
            $this->_yotpoConfig->log("AbstractApi::sendApiRequest() - request: ", "debug", [["path" => $path, "params" => $data, "method" => $method, "timeout" => $timeout, "contentType" => $contentType]]);

            $this->_clearResponseData();
            $this->curl->reset();

            if ($contentType) {
                $this->curl->setHeaders(
                    [
                    'Content-Type' => $contentType
                    ]
                );
            }

            $this->curl->setTimeout($timeout);

            $this->curl->{strtolower($method)}(
                $this->_yotpoConfig->getYotpoApiUrl($path),
                $data
            );

            if (($this->_getCurlStatus() == 401) && (self::$_retryCount < self::NUMBER_OF_RETRY)) {
                $this->_yotpoConfig->log(
                    "AbstractApi::sendApiRequest() - response: ",
                    "debug",
                    $this->_prepareCurlResponseData()
                );
                $data['utoken'] = $this->oauthAuthentication(
                    $this->_yotpoConfig->getCurrentStoreId(),
                    ScopeInterface::SCOPE_STORES,
                    true
                );
                $this->sendApiRequest($path, $data, $method, $timeout, $contentType);
                self::$_retryCount++;
            }
            self::$_retryCount = 0;
            $this->_yotpoConfig->log("AbstractApi::sendApiRequest() - response: ", "debug", $this->_prepareCurlResponseData());
            return $this->_prepareCurlResponseData();
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AbstractApi::sendApiRequest() Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }

    /**
     * @method oauthAuthentication
     * @param int|null $scopeId
     * @param string|null $scope
     * @param bool $forceCreateTokenFlag
     * @return mixed
     */
    public function oauthAuthentication($scopeId = null, $scope = null, $forceCreateTokenFlag = false)
    {
        try {
            if (($token = $this->checkIfTokenExist($scopeId)) && !$forceCreateTokenFlag) {
                return $token;
            }
            $app_key = $this->_yotpoConfig->getAppKey($scopeId, $scope);
            $secret = $this->_yotpoConfig->getSecret($scopeId, $scope);
            if (!($app_key && $secret)) {
                $this->_yotpoConfig->log("AbstractApi::oauthAuthentication({$scopeId}, {$scope}) - Missing app key or secret", "debug", ['$app_key' => $app_key]);
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
                $this->_yotpoConfig->log("AbstractApi::oauthAuthentication({$scopeId}, {$scope}) - error: no response from api", "error", ['$app_key' => $app_key]);
                return null;
            }
            $token = (is_object($result['body']) && property_exists($result['body'], "access_token")) ? $result['body']->access_token : false;
            if (!$token) {
                $this->_yotpoConfig->log("AbstractApi::oauthAuthentication({$scopeId}, {$scope}) - error: no access token received", "error", ['$app_key' => $app_key]);
                return null;
            }
            $this->resourceConfig->saveConfig(
                YotpoConfig::XML_PATH_YOTPO_TOKEN,
                $this->encryptor->encrypt($token),
                ScopeInterface::SCOPE_STORES,
                $scopeId
            );
            return $token;
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AbstractApi::oauthAuthentication({$scopeId}, {$scope}) - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return null;
        }
    }

    /**
     * @method checkIfTokenExist
     * @param  int|null $scopeId
     * @param  string|null $scope
     * @return mixed
     */
    protected function checkIfTokenExist($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        $token = $this->resourceConfig->getConfig(YotpoConfig::XML_PATH_YOTPO_TOKEN, $scope, $scopeId);
        return $token ? $this->encryptor->decrypt($token) : false;
    }
}
