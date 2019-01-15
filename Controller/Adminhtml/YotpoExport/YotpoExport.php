<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\YotpoExport;

class YotpoExport extends \Magento\Backend\App\Action
{

/**
* @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory

protected $_messageManager;

public function __construct(
	\Magento\Backend\App\Action\Context $context,
	\Magento\Framework\App\Request\Http $request,
	\Magento\Framework\App\Response\Http $response,
	\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
	\Psr\Log\LoggerInterface $logger
) {
	$this->_request = $request;
	$this->_response = $response; 
	$this->_logger = $logger;
	$this->_productCollectionFactory = $productCollectionFactory;
	$this->_messageManager = $context->getMessageManager();          
	parent::__construct($context);
}


public function execute()
{
	$outputFile = "productsYotpo.csv";
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		try{

			$iteration = 0;
			$saveData = array();
			ob_start();
			$fp = fopen($outputFile, 'w'); 
			$storeId = $this->getRequest()->getParam('store_id');  // gets the store id
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface'); //Get store manager
			$collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addStoreFilter($storeId);    
			foreach ($collection as $product) { //Go over products array, which is the product collection
				$typeProduct = $product->getTypeId();
				if($typeProduct != 'configurable' && $typeProduct != 'grouped'){
					$_product = $objectManager->get('Magento\Catalog\Model\Product')->load($product->getId()); // crashes when it cant get the product
					$saveDate["product_id"] = $_product->getId();
					$saveData["product_name"] = $_product->getName();
					$saveData['product_description'] = $_product->getDescription();
					$saveData['product_url'] = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).$_product->getUrlKey().'.html';
			 		if (($_product->getImage() != 'no_selection') && ($_product->getImage() != NULL)){ //Pull Image URL only in case there's a pic associated with the product
			 		$saveData['product_image_url'] = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'catalog/product/'.$_product->getImage();
			 	}
			 		else { //If there's no image associated with the product, keep the column empty
			 		$saveData['product_image_url'] = '';
			 	}
			 	$saveData['product_price'] =$_product->getPrice();
				$saveData['Currency'] = 'USD'; //Statically set as USD as Yotpo currently supports USD only
				$saveData['spec_upc'] = '';
				$saveData['spec_sku'] = $_product->getSku();
				$saveData['spec_brand'] = '';
				$saveData['spec_mpn'] = '';
				$saveData['spec_isbn'] = '';
				$saveData['blacklisted'] = 'false'; //Yotpo related
				$saveData['product_group'] = ''; //Yotpo related  
				if($iteration==0) fputcsv($fp, array_keys($saveData));
				fputcsv($fp, $saveData);
				$iteration++;
				}
			} 
			fclose($fp);
			ob_clean();
			flush();
		} catch(\Exception $e) {
			$this->_logger->addDebug('Failed to export product catalog. Error: '.$e);
		}
	}else if ($_SERVER['REQUEST_METHOD'] === 'GET'){ # send and delete file
		try{
			header('Content-Description: File Transfer');
			header('Content-type: application/csv');
			header('Content-Disposition: attachment; filename='.basename($outputFile));
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($outputFile));
			readFile($outputFile);
			unlink($outputFile);
		} catch(\Exception $e) {
			$this->_logger->addDebug('Failed to export product catalog. Error: '.$e);
		}
	}
}
}