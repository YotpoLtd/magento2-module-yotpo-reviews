<?php

namespace Yotpo\Yotpo\Cron;

use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class OrderSync
{
	//max amount of orders to export
	const MAX_ORDERS_TO_EXPORT = 5000;
	const MAX_BULK_SIZE        = 200;

	/**
	 * @var YotpoHelper
	 */
	protected $_yotpoHelper;

	/**
	 * @var YotpoApiClient
	 */
	protected $_yotpoApi;

	/**
	 * @var OrderCollectionFactory
	 */
	protected $_orderCollectionFactory;

	/**
	 * @var ManagerInterface
	 */
	protected $_messageManager;

	/**
	 * @method __construct
	 * @param  Context                $context
	 * @param  YotpoHelper            $yotpoHelper
	 * @param  YotpoApiClient         $yotpoApi
	 * @param  OrderCollectionFactory $orderCollectionFactory
	 */
	public function __construct(
			Context $context,
			YotpoHelper $yotpoHelper,
			YotpoApiClient $yotpoApi,
			OrderCollectionFactory $orderCollectionFactory
	) {
			$this->_yotpoHelper = $yotpoHelper;
			$this->_yotpoApi = $yotpoApi;
			$this->_orderCollectionFactory = $orderCollectionFactory;
			$this->_messageManager = $context->getMessageManager();
	}

	public function execute()
	{
			try {
					$success = true;
					$stores = $this->getRequest()->getParam("stores");
					if (!$stores) {
							$stores = $this->_yotpoHelper->getAllStoreIds(true);
					}

					foreach ($stores as $storeId) {
							try {
									$this->_yotpoHelper->emulateFrontendArea($storeId, true);
									$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} ...", "info");

									if (!(($appKey = $this->_yotpoHelper->getAppKey()) && ($secret = $this->_yotpoHelper->getSecret()))) {
											$this->_messageManager->addError(__('Please make sure you insert your APP KEY and SECRET and save configuration before trying to export past orders'));
											return;
									}
									if (!($token = $this->_yotpoApi->oauthAuthentication())) {
											$this->_messageManager->addError(__("Please make sure the APP KEY and SECRET you've entered are correct"));
											return;
									}

									$ordersCollection = $this->_yotpoHelper->getOrderCollection();
									$ordersCollection
											->addAttributeToFilter('status', $this->_yotpoHelper->getCustomOrderStatus())
											->addAttributeToFilter('store_id', $storeId)
											->addAttributeToSort('created_at', 'DESC')
											->setPageSize(self::MAX_BULK_SIZE);

									$pages = $ordersCollection->getLastPageNumber();
									$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} - pages: {$pages}.", "info");
									$offset = 0;
									do {
											try {
													$offset++;
													$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} - page {$offset} ...", "info");
													$ordersCollection->setCurPage($offset)->load();
													$orders = $this->_yotpoApi->prepareOrdersData($ordersCollection);
													$ordersCount = count($orders);
													$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} - pages: {$pages} - orders count: " . $ordersCount, "info");
													if ($ordersCount > 0) {
															$resData = $this->_yotpoApi->massCreatePurchases($orders, $token);
															if ($resData['status'] != 200) {
																	$success = false;
																	$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} - page {$offset} [FAILURE]", "error", $resData);
															} else {
																	$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} - page {$offset} [SUCCESS]", "info");
															}
													}
											} catch (\Exception $e) {
													$this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders for store ID: {$storeId} - page {$offset}: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
											}
											$ordersCollection->clear();
									} while ($offset <= (self::MAX_ORDERS_TO_EXPORT / self::MAX_BULK_SIZE) && $offset < $pages);

									$this->_yotpoHelper->log("Yotpo - Processing past orders for store ID: {$storeId} [DONE]", "info");
							} catch (\Exception $e) {
									$this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders: " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
							}
							$this->_yotpoHelper->stopEnvironmentEmulation();
					}
			} catch (\Exception $e) {
					$this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders. " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
			}

			if ($success) {
					$this->_messageManager->addSuccess(__("Past orders were exported successfully. Emails will be sent to your customers within 24 hours, and you will start to receive reviews."));
					$this->_yotpoHelper->log("Yotpo - Past orders were exported successfully", "info");
			} else {
					$this->_messageManager->addError(__("An error occured, please try again later."));
					$this->_yotpoHelper->log("Yotpo Exception - Failed to export past orders. " . $e->getMessage() . "\n" . print_r($e->getTraceAsString(), true), "error");
			}

			$this->getResponse()->setBody(1);
			return;
	}
}
