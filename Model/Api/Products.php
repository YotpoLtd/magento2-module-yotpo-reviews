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
    const TTL = 86400; // 60 * 60 * 24 seconds

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
        $return = [
            "average_score" => 0.0,
            "reviews_count" => 0
        ];

        try {
            $storeId = $this->_yotpoConfig->getCurrentStoreId();
            $snippet = $this->richsnippet->getSnippetByProductIdAndStoreId($productId, $storeId);

            if ($snippet && $snippet->isValid()) {
                $return["average_score"] = $snippet->getAverageScore();
                $return["reviews_count"] = $snippet->getReviewsCount();
            } else {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->sendApiRequest(self::PATH . "/" . $this->_yotpoConfig->getAppKey() . '/' . $productId . "/bottomline/", [], "get", 2);

                if ($res["status"] == 200) {
                    $return["average_score"] = round($res["body"]->response->bottomline->average_score, 2);
                    $return["reviews_count"] = $res["body"]->response->bottomline->total_reviews;
                }

                if ($snippet == null) {
                    $snippet = $this->richsnippet;
                    $snippet->setProductId($productId);
                    $snippet->setStoreId($storeId);
                }

                $snippet->setAverageScore($return["average_score"]);
                $snippet->setReviewsCount($return["reviews_count"]);
                $snippet->setExpirationTime(date('Y-m-d H:i:s', time() + self::TTL));
                $snippet->save();
            }
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("Products::getRichSnippet() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }

        return $return;
    }
}
