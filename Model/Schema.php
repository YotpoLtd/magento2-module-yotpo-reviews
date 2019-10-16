<?php

namespace Yotpo\Yotpo\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Escaper;
use Magento\GroupedProduct\Model\Product\Type\Grouped as ProductTypeGrouped;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Schema
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @method __construct
     * @param  YotpoConfig    $yotpoConfig
     * @param  ProductFactory $productFactory
     * @param  Escaper        $escaper
     */
    public function __construct(
        YotpoConfig $yotpoConfig,
        ProductFactory $productFactory,
        Escaper $escaper
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->productFactory = $productFactory;
        $this->escaper = $escaper;
    }

    /**
     * @method getProductMainImageUrl
     * @param  Product $product
     * @return string
     */
    private function getProductMainImageUrl(Product $product)
    {
        if (($filePath = $product->getData("image"))) {
            return (string) $this->yotpoConfig->getMediaUrl("catalog/product", $filePath);
        }
        return "";
    }

    /**
     * @method prepareProductsData
     * @param  Order $order
     * @return array
     */
    private function prepareProductsData(Order $order)
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
                                $product = $groupProductsParents[$productId] = $this->productFactory->create()->load($productId);
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
                                'image'       => $this->getProductMainImageUrl($product),
                                'description' => $this->escaper->escapeHtml(strip_tags($product->getDescription())),
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
                    $this->yotpoConfig->log("Schema::prepareProductsData() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                }
            }
        } catch (\Exception $e) {
            $this->yotpoConfig->log("Schema::prepareProductsData() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }

        return $productsData;
    }

    /**
     * @method prepareOrdersData
     * @param  OrderCollection $ordersCollection
     * @return array
     */
    public function prepareOrdersData(OrderCollection $ordersCollection)
    {
        $ordersData = [];

        try {
            foreach ($ordersCollection as $order) {
                if (($_order = $this->prepareOrderData($order))) {
                    $ordersData[] = $_order;
                }
            }
        } catch (\Exception $e) {
            $this->yotpoConfig->log("Schema::prepareOrdersData() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return [];
        }

        return $ordersData;
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
            $this->yotpoConfig->log("Schema::prepareOrderData() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return [];
        }

        return $orderData;
    }
}
