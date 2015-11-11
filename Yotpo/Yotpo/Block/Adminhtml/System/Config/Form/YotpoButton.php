<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form;

class YotpoButton extends \Magento\Config\Block\System\Config\Form\Field
{

   public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    \Psr\Log\LoggerInterface $logger,         
    array $data = []
    ) {
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;    
        $this->_context = $context;
        parent::__construct($context, $data);
    }

    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
    }
 
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/yotpobutton.phtml');
        }
        return $this;
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
 
    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxExportUrl()     {
        return '/admin/massmap/yotpocontroller/yotpocontroller/';
    }

    public function getStoreId()
    {
        return $this->_context->getStoreManager()->getStore()->getId();
    }
 
    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
       $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
            'id'        => 'yotpo_button',
            'label'     => __('Generate reviews for my past orders'),
            'onclick'   => 'javascript:exportOrders(); return false;',
            ]
        );
        return $button->toHtml();
    }
}
?>