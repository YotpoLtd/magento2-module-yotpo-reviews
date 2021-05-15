<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form;

class YotpoExport extends \Magento\Config\Block\System\Config\Form\Field
{

   public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    \Magento\Framework\App\Request\Http $request,
    array $data = []
    ) {
        $this->_messageManager = $messageManager;
        $this->_request = $request;
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
            $this->setTemplate('system/config/yotpoexport.phtml');
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
    public function getExportUrl()     { 
        return $this->getUrl('massmap/yotpoexport/yotpoexport/', ['_secure' => $this->getRequest()->isSecure()]);
    }





    public function getStoreId()
    { 
        if ($this->_request->getParam('store', 0)) {
            return $this->_request->getParam('store', 0);
        } else {
            return $this->_context->getStoreManager()->getStore()->getId();
        }
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
            'id'        => 'yotpo_export',
            'label'     => __('Generate product catalog Yotpo format'),
            'onclick'   => 'javascript:exportCatalog(); return true;',
            ]
        );
        return $button->toHtml();
    }

}