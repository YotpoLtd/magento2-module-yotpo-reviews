<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\Settings;


class Index extends \Magento\Backend\App\Action
{
	protected $_coreRegistry = null;


    public function __construct(
      \Magento\Backend\App\Action\Context $context
      )
    {
        parent::__construct($context);
    }

      public function execute(){
        $this->_view->loadLayout();
  			$this->_setActiveMenu('Magento_Reports::Yotpo')->_addBreadcrumb(
  			__('Yotpo Settings'),
  			__('Yotpo Settings')
  			);
  			$this->_view->getPage()->getConfig()->getTitle()->prepend(__('Yotpo Settings'));
  			$this->_view->renderLayout();
      }


}
