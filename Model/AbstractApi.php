<?php

namespace Yotpo\Yotpo\Model;

use Magento\Catalog\Model\ProductFactory;
use Yotpo\Yotpo\Lib\Http\Client\Curl;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class AbstractApi
{
    const DEFAULT_TIMEOUT = 90;

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
     * @method __construct
     * @param  Curl           $curl
     * @param  ProductFactory $productFactory
     * @param  YotpoConfig    $yotpoConfig
     */
    public function __construct(
        Curl $curl,
        ProductFactory $productFactory,
        YotpoConfig $yotpoConfig
    ) {
        $this->curl = $curl;
        $this->productFactory = $productFactory;
        $this->_yotpoConfig = $yotpoConfig;
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

            $this->curl->setOption(CURLOPT_TIMEOUT, $timeout);

            $this->curl->{strtolower($method)}(
                $this->_yotpoConfig->getYotpoApiUrl($path),
                $data
            );

            $this->_yotpoConfig->log("AbstractApi::sendApiRequest() - response: ", "debug", $this->_prepareCurlResponseData());
            return $this->_prepareCurlResponseData();
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AbstractApi::sendApiRequest() Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }

    /**
     * @method oauthAuthentication
     * @param  int|null $scopeId
     * @param  string|null $scope
     * @return mixed
     */
    public function oauthAuthentication($scopeId = null, $scope = null)
    {
        try {
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
            return $token;
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AbstractApi::oauthAuthentication({$scopeId}, {$scope}) - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return null;
        }
    }
}
