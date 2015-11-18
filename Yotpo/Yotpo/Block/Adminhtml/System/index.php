<?php

namespace Yotpo\Yotpo\Block\Adminhtml\System;



class Index extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @return void
     */


    protected $_blockGroup = 'Yotpo_Yotpo';

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Yotpo\Yotpo\Block\Config $config,
        array $data = []
    ) {
         $this->_config = $config;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
       $this->_blockGroup = 'Yotpo_Yotpo';
        $this->_controller = 'adminhtml_Settings_index';
        parent::_construct();
    }

     protected function _prepareLayout()
    {
    }



        /**
     * Returns URL for save action
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormActionUrl()
    {

        return $this->getUrl('adminhtml/settings/save');
    }



}

    