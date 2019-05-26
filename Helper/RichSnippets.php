<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
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
    protected $_richsnippet;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @method __construct
     * @param  Context $context
     * @param  YotpoHelper $yotpoHelper
     * @param  YotpoApiClient $yotpoApi
     * @param  Richsnippet $richsnippet
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi,
        Richsnippet $richsnippet,
        StoreManagerInterface $storeManager
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
        $this->_richsnippet = $richsnippet;
        $this->_storeManager = $storeManager;
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

            $snippet = $this->_richsnippet->getSnippetByProductIdAndStoreId($productId, $storeId);

            if (!$snippet || !$snippet->isValid()) {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->_yotpoApi->sendApiRequest("products/" . $this->_yotpoHelper->getAppKey() . '/' . $productId . "/bottomline/", [], "get", 2);

                if ($res["status"] != 200) {
                    //product not found or feature disabled.
                    return [];
                }

                $body = $res["body"];
                $averageScore = $body->response->bottomline->average_score;
                $reviewsCount = $body->response->bottomline->total_reviews;
                $ttl = 60 * 60 * 24; // seconds

                if ($snippet == null) {
                    $snippet = $this->_richsnippet;
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
            $this->_yotpoHelper->log("RichSnippets::getRichSnippet() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
        return [];
    }
}
