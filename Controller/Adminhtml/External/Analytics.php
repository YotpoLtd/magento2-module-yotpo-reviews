<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\External;

use Magento\Framework\Controller\ResultFactory;

class Analytics extends \Magento\Backend\App\Action
{
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl('https://yap.yotpo.com/#/tools/conversions_dashboard/engagement');
    }
}
