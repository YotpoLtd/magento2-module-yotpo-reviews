<?php

namespace Yotpo\Yotpo\Plugin\Backend\Block\Dashboard;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Plugin for Grids
 */
class Grids
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        RequestInterface $request,
        YotpoHelper $yotpoHelper
    ) {
        $this->_request = $request;
        $this->_yotpoHelper = $yotpoHelper;
    }

    protected function getRequest()
    {
        return $this->_request;
    }

    public function isEnabled()
    {
        if (($storeId = $this->getRequest()->getParam("store", 0))) {
            return $this->_yotpoHelper->isEnabled($storeId, ScopeInterface::SCOPE_STORE);
        } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
            return $this->_yotpoHelper->isEnabled($websiteId, ScopeInterface::SCOPE_WEBSITE);
        } else {
            return $this->_yotpoHelper->isEnabled();
        }
    }

    /**
     * @method beforeRenderResult
     * @param  \Magento\Backend\Block\Dashboard\Grids $subject
     * @return array
     */
    public function beforeToHtml(
        \Magento\Backend\Block\Dashboard\Grids $subject
    ) {
        if ($this->isEnabled()) {
            $subject->addTab(
                'yotpo_reviews',
                [
                    'label' => __('Yotpo Reviews'),
                    'url' => $subject->getUrl('yotpo_yotpo/*/YotpoReviews', ['_current' => true]),
                    'class' => 'ajax'
                ]
            );
        }
    }
}
