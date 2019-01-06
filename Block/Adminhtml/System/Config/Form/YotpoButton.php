<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class YotpoButton extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Template path
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::system/config/yotpobutton.phtml';

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @method __construct
     * @param  Context          $context
     * @param  ManagerInterface $messageManager
     * @param  array            $data
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        $this->_messageManager = $messageManager;
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * @method _construct
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
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxExportUrl()
    {
        return $this->getUrl('massmap/yotpocontroller/yotpocontroller/', ['_secure' => $this->getRequest()->isSecure()]);
    }

    public function getStoreId()
    {
        if ($this->getRequest()->getParam('store', 0)) {
            return $this->getRequest()->getParam('store', 0);
        } else {
            return $this->_storeManager->getStore()->getId();
        }
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        return $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData([
                'id'        => 'yotpo_button',
                'label'     => __('Generate reviews for my past orders'),
                'onclick'   => 'javascript:exportOrders(); return false;',
            ])
            ->toHtml();
    }
}
