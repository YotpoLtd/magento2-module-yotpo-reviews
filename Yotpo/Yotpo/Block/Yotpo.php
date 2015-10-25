<?php
namespace Yotpo\Yotpo\Block;
class Yotpo extends \Magento\Framework\View\Element\Template
{
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\UrlInterface $urlinterface,
    array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_urlinterface = $urlinterface;
        parent::__construct($context, $data);
    }

	protected function _construct()
    {

        parent::_construct();

    }

    public function getAppKey() {
    	return '7QH55QIfYyIxS1DrNKddf0o2j1W4X72O3QTtLDih';
    }

    public function getProduct()
	{
		if (!$this->hasData('product')) {
            $this->setData('product', $this->_coreRegistry->registry('current_product'));
        }
        return $this->getData('product');
    }

    public function getProductId() {
    	$_product = $this->getProduct();     	
    	return $_product->getId();
    }

    public function getProductName() {
        $_product = $this->getProduct();
        $productName = $_product->getName();
        return htmlspecialchars($productName);
    }
    
    public function getProductDescription()
    {
        $_product = $this->getProduct();
        return $_product->getShortDescription();
        
    }

    public function getProductUrl()
    {
        return $this->_urlinterface->getCurrentUrl();
    }


        /**
     * Returns URL for save action
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('adminhtml/*/save');
    }
    
}