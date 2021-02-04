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
     * @var OrderStatusHistoryFactory
     */
    private $orderStatusHistoryFactory;

    /**
     * @method __construct
     * @param  YotpoConfig               $yotpoConfig
     * @param  ProductFactory            $productFactory
     * @param  Escaper                   $escaper
     * @param  OrderStatusHistoryFactory $orderStatusHistoryFactory
     */
    public function __construct(
        YotpoConfig $yotpoConfig,
        ProductFactory $productFactory,
        Escaper $escaper,
        OrderStatusHistoryFactory $orderStatusHistoryFactory
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->productFactory = $productFactory;
        $this->escaper = $escaper;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
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
                        $this->yotpoConfig->log("Skipped ProductId :  " . $orderItem->getProductId(), "debug");
                        continue;
                    }
                    if (
                        $orderItem->getData('amount_refunded') >= $orderItem->getData('row_total_incl_tax') ||
                        $orderItem->getData('qty_ordered') <= ($orderItem->getData('qty_refunded') + $orderItem->getData('qty_canceled'))
                    ) {
                        //Skip if item is fully canceled or refunded
                        $this->yotpoConfig->log("Skipped Canceled or refunded ProductId :  " . $orderItem->getProductId(), "debug");
                        continue;
                    }
                    if ($orderItem->getProductType() === ProductTypeGrouped::TYPE_CODE && isset($productsData[$product->getId()])) {
                        $productsData[$product->getId()]['price'] += $orderItem->getData('row_total_incl_tax') - $orderItem->getData('amount_refunded');
                    } else {
                        $productsData[$product->getId()] = [
                                'name'        => $product->getName(),
                                'url'         => $product->getProductUrl(),
                                'image'       => $this->getProductMainImageUrl($product),
                                'description' => $this->escaper->escapeHtml(strip_tags($product->getDescription())),
                                'price'       => $orderItem->getData('row_total_incl_tax') - $orderItem->getData('amount_refunded'),
                                'specs'       => array_filter(
                                    [
                                    'external_sku' => $product->getSku(),
                                    'upc'          => $product->getUpc(),
                                    'isbn'         => $product->getIsbn(),
                                    'mpn'          => $product->getMpn(),
                                    'brand'        => $product->getBrand() ? $product->getAttributeText('brand') : null,
                                    ]
                                ),
                            ];
                    }
                    $this->yotpoConfig->log("Processed ProductId :  " . $orderItem->getProductId(), "debug");
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
            $this->yotpoConfig->log("Processing OrderId :  " . $order->getIncrementId(), "debug");
            $orderData['products'] = $this->prepareProductsData($order);
            if (!$orderData['products']) {
                $this->yotpoConfig->log("Skipped OrderId :  " . $order->getIncrementId(), "debug");
                return [];
            }
            $orderData['order_id'] = $order->getIncrementId();
            $orderData['order_date'] = $order->getCreatedAt();
            $orderData['fulfillment_date'] = $this->getOrderFulfillmentDate($order);
            $orderData['currency_iso'] = $order->getOrderCurrency()->getCode();
            $orderData['email'] = $order->getCustomerEmail();
            $orderData['customer_name'] = trim($order->getCustomerFirstName() . ' ' . $order->getCustomerLastName());
            $orderData['platform'] = 'magento';
            if (!$orderData['customer_name'] && ($billingAddress = $order->getBillingAddress())) {
                $orderData['customer_name'] = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
            }
            if (!$order->getCustomerIsGuest()) {
                $orderData['user_reference'] = $order->getCustomerId();
            }
            if (($fulfillmentDate = $this->getOrderFulfillmentDate($order))) {
                $orderData['fulfillment_status'] = 'fulfilled';
                $orderData['fulfillment_status_date'] = $fulfillmentDate;
            }
        } catch (\Exception $e) {
            $this->yotpoConfig->log("Schema::prepareOrderData() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return [];
        }

        return $orderData;
    }

    private function getOrderFulfillmentDate(Order $order)
    {
        $lastStatus = $this->orderStatusHistoryFactory->create()->getCollection()
            ->addFieldToSelect(["id","created_at"])
            ->addFieldToFilter("order_id", $order->getId())
            ->addFieldToFilter("store_id", $order->getStoreId())
            ->setOrder('created_at', 'desc')
            ->setOrder('id', 'desc')
            ->setPageSize(1)
            ->getFirstItem();
        if ($lastStatus && $lastStatus->getId()) {
            return $lastStatus->getCreatedAt();
        } elseif (($lastStatus = $order->getStatusHistoryCollection()->getFirstItem())) {
            return $lastStatus->getCreatedAt();
        } else {
            return null;
        }
    }
}
