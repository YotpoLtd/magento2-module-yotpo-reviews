<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Yotpo\Yotpo\Helper;

class RichSnippets extends \Magento\Framework\App\Helper\AbstractHelper
{

    private $_config;
    private $_model;
    private $_helper;
    protected $_storeManager;    

    public function __construct(
            \Yotpo\Yotpo\Block\Config $config,
            \Yotpo\Yotpo\Model\Richsnippet $model,
            \Yotpo\Yotpo\Helper\ApiClient $helper,
            \Magento\Store\Model\StoreManagerInterface $storeManager     
            ) {
        $this->_config = $config;
        $this->_model = $model;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
    }

    public function getRichSnippet() {

        try {
            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Framework\Registry')->registry('current_product');//get current product
            $productId = $product->getId();
            $storeId = $this->_storeManager->getStore()->getId();
            
            $snippet = $this->_model->getSnippetByProductIdAndStoreId($productId, $storeId);
            
            
            if (($snippet == null) || (!$snippet->isValid())) {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from yotpo api
                $res = $this->_helper->createApiGet("products/" . ($this->_config->getAppKey()) . "/richsnippet/" . $productId, 2);

                print_r(json_encode($res));
                if ($res["code"] != 200) {
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

                return array("average_score" => $averageScore, "reviews_count" => $reviewsCount);
            }
            return array("average_score" => $snippet->getAverageScore(), "reviews_count" => $snippet->getReviewsCount());
        } catch (Exception $e) {
            Mage::log($e);
        }
        return array();
    return true;
        
    }

}
