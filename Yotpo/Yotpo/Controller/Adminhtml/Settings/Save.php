<?php


namespace Yotpo\Yotpo\Controller\Adminhtml\Settings;

class Save extends \Magento\Backend\App\Action
{

 
   public function __construct(
        \Magento\Backend\App\Action\Context $Context,
        \Yotpo\Yotpo\Block\Config $config
    ) {
        $this->_config = $config;
        parent::__construct($Context);
    }   

    public function execute()
    {
            try {
                $app_key = $this->getRequest()->getParam('app_key');
                $secret = $this->getRequest()->getParam('secret');
                $show_widget = $this->getRequest()->getParam('show_widget');
                $show_buttomline = $this->getRequest()->getParam('show_buttomline');
                $this->_config->setAppKey($app_key);
                $this->_config->setSecret($secret);
                $this->_config->setWidgetEnabled($show_widget);
                $this->_config->setBottomlineEnabled($show_buttomline);
                
                $this->messageManager->addSuccess(__('Yotpo Settings Saved'));  
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }


        $this->_redirect('adminhtml/*/');
    }
}
