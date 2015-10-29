<?php

namespace Magento\Newsletter\Controller\Adminhtml;


class Subscriber extends \Magento\Backend\App\Action
{

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_logger->addDebug('In admin');
        die("In admin");
    }
}
