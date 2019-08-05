<?php

namespace Yotpo\Yotpo\Model\Api;

use Magento\Catalog\Model\ProductFactory;
use Yotpo\Yotpo\Lib\Http\Client\Curl;
use Yotpo\Yotpo\Model\AbstractApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\Richsnippet;

class Products extends AbstractApi
{
    const PATH = 'products';

    /**
     * @var Richsnippet
     */
    private $richsnippet;

    /**
     * @method __construct
     * @param  Curl           $curl
     * @param  ProductFactory $productFactory
     * @param  YotpoConfig    $yotpoConfig
     * @param  Richsnippet    $richsnippet
     */
    public function __construct(
        Curl $curl,
        ProductFactory $productFactory,
        YotpoConfig $yotpoConfig,
        Richsnippet $richsnippet
    ) {
        parent::__construct($curl, $productFactory, $yotpoConfig);
        $this->richsnippet = $richsnippet;
    }

    /**
     * @method getRichSnippet
     * @return array
     */
    public function getRichSnippet($productId = null)
    {
        try {
            $storeId = $this->_yotpoConfig->getCurrentStoreId();
            $snippet = $this->richsnippet->getSnippetByProductIdAndStoreId($productId, $storeId);

            if (!$snippet || !$snippet->isValid()) {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->sendApiRequest(self::PATH . "/" . $this->_yotpoConfig->getAppKey() . '/' . $productId . "/bottomline/", [], "get", 2);

                if ($res["status"] != 200) {
                    //product not found or feature disabled.
                    return [];
                }

                $body = $res["body"];
                $averageScore = $body->response->bottomline->average_score;
                $reviewsCount = $body->response->bottomline->total_reviews;
                $ttl = 60 * 60 * 24; // seconds

                if ($snippet == null) {
                    $snippet = $this->richsnippet;
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
            $this->_yotpoConfig->log("Products::getRichSnippet() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
        return [];
    }
}
