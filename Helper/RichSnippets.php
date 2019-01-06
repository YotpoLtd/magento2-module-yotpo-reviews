<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Framework\App\Helper\Context;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;
use Yotpo\Yotpo\Model\Richsnippet;

class RichSnippets extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @var Richsnippet
     */
    protected $_richsnippents;

    /**
     * @method __construct
     * @param  Context        $context
     * @param  YotpoHelper    $yotpoHelper
     * @param  YotpoApiClient $yotpoApi
     * @param  Richsnippet    $richsnippents
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi,
        Richsnippet $richsnippents
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
        $this->_richsnippents = $richsnippents;
        parent::__construct($context);
    }

    /**
     * @method getRichSnippet
     * @return array
     */
    public function getRichSnippet()
    {
        try {
            $product = $this->_yotpoHelper->getCurrentProduct();

            $productId = $product->getId();
            $storeId = $this->_storeManager->getStore()->getId();
            $snippet = $this->_model->getSnippetByProductIdAndStoreId($productId, $storeId);

            if (!$snippet || !$snippet->isValid()) {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->_yotpoApi->sendApiRequest("products/" . ($this->_yotpoHelper->getAppKey()) . "/richsnippet/" . $productId, "get", 2);

                if ($res["status"] != 200) {
                    //product not found or feature disabled.
                    return "";
                }

                $body = $res["body"];
                $averageScore = $body->response->rich_snippet->reviews_average;
                $reviewsCount = $body->response->rich_snippet->reviews_count;
                $ttl = $body->response->rich_snippet->ttl;

                if ($snippet == null) {
                    $snippet = $this->_model;
                    $snippet->setProductId($productId);
                    $snippet->setStoreId($storeId);
                }

                $snippet->setAverageScore($averageScore);
                $snippet->setReviewsCount($reviewsCount);
                $snippet->setExpirationTime(date('Y-m-d H:i:s', time() + $ttl));
                $snippet->save();

                return [
                    "average_score" => $averageScore,
                    "reviews_count" => $reviewsCount
                ];
            }
            return [
                "average_score" => $snippet->getAverageScore(),
                "reviews_count" => $snippet->getReviewsCount()
            ];
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("Yotpo RichSnippets Exception: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
        }
        return [];
    }
}
