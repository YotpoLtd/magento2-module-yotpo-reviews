<?php
namespace Yotpo\Yotpo\Block;
class Yotpo extends \Magento\Framework\View\Element\Template
{
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\UrlInterface $urlinterface,
    \Yotpo\Yotpo\Block\Config $config,
    array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_urlinterface = $urlinterface;
        $this->_config = $config;
        parent::__construct($context, $data);
    }

	protected function _construct()
    {
        parent::_construct();
    }

    public function getProduct()
	{
		if (!$this->hasData('product')) {
            $this->setData('product', $this->_coreRegistry->registry('current_product'));
        }
        return $this->getData('product');
    }

    public function getProductId() {
    	$product = $this->getProduct();     	
    	return $product->getId();
    }

    public function getProductName() {
        $product = $this->getProduct();
        $productName = $product->getName();
        return htmlspecialchars($productName);
    }
    
    public function getProductDescription()
    {
        $product = $this->getProduct();
        return $product->getShortDescription();
        
    }

    public function getProductUrl()
    {
        return $this->_urlinterface->getCurrentUrl();
    }    

    public function isRenderWidget()
    {
        return $this->getProduct() != null && ($this->_config->getShowWidget() || $this->getData('fromHelper'));
    }    

    private function isProductPage()
    {
        return $this->getProduct() != null;
    }     
}