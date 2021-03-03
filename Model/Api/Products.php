<?php

namespace Yotpo\Yotpo\Model\Api;

use Magento\Catalog\Model\ProductFactory;
use Yotpo\Yotpo\Lib\Http\Client\Curl;
use Yotpo\Yotpo\Model\AbstractApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;
use Yotpo\Yotpo\Model\Richsnippet;
use Yotpo\Yotpo\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Encryption\EncryptorInterface;

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
     * @param  Curl                $curl
     * @param  ProductFactory      $productFactory
     * @param  YotpoConfig         $yotpoConfig
     * @param  ResourceConfig      $resourceConfig
     * @param  Richsnippet         $richsnippet
     * @param  EncryptorInterface  $encryptor
     */
    public function __construct(
        Curl $curl,
        ProductFactory $productFactory,
        YotpoConfig $yotpoConfig,
        ResourceConfig $resourceConfig,
        Richsnippet $richsnippet,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($curl, $productFactory, $yotpoConfig, $resourceConfig, $encryptor);
        $this->richsnippet = $richsnippet;
    }

    /**
     * @method getRichSnippet
     * @return array
     */
    public function getRichSnippet($productId = null)
    {
        $rich_snippet_data = [
            "average_score" => 0.0,
            "reviews_count" => 0
        ];

        try {
            $storeId = $this->_yotpoConfig->getCurrentStoreId();
            $snippet = $this->richsnippet->getSnippetByProductIdAndStoreId($productId, $storeId);

            if ($snippet && $snippet->isValid()) {
                $rich_snippet_data["average_score"] = $snippet->getAverageScore();
                $rich_snippet_data["reviews_count"] = $snippet->getReviewsCount();
            } else {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->sendApiRequest(self::PATH . "/" . $this->_yotpoConfig->getAppKey() . '/' . $productId . "/bottomline/", [], "get", 2);

                if ($res["status"] == 200) {
                    $rich_snippet_data["average_score"] = round($res["body"]->response->bottomline->average_score, 2);
                    $rich_snippet_data["reviews_count"] = $res["body"]->response->bottomline->total_reviews;
                }

                if ($snippet == null) {
                    $snippet = $this->richsnippet;
                    $snippet->setProductId($productId);
                    $snippet->setStoreId($storeId);
                }

                $snippet->setAverageScore($rich_snippet_data["average_score"]);
                $snippet->setReviewsCount($rich_snippet_data["reviews_count"]);
                $snippet->setExpirationTime(date('Y-m-d H:i:s', time() + self::TTL));
                $snippet->save();
            }
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("Products::getRichSnippet() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }

        return $rich_snippet_data;
    }
}
