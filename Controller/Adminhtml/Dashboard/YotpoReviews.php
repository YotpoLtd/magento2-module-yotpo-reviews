<?php

namespace Yotpo\Yotpo\Controller\Adminhtml\Dashboard;

use Magento\Backend\Controller\Adminhtml\Dashboard\AjaxBlock;

class YotpoReviews extends AjaxBlock
{
    /**
     * Gets Yotpo reviews tab
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(\Yotpo\Yotpo\Block\Adminhtml\Dashboard\Tab\YotpoReviews::class)
            ->setId('yotpoReviewsTab')
            ->toHtml();
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($output);
    }
}
