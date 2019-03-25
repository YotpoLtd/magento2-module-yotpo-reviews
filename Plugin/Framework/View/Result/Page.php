<?php

namespace Yotpo\Yotpo\Plugin\Framework\View\Result;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Context;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Plugin for Page View
 */
class Page
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper
    ) {
        $this->_context = $context;
        $this->_yotpoHelper = $yotpoHelper;
    }

    /**
     * @method beforeRenderResult
     * @param  \Magento\Framework\View\Result\Page $subject
     * @param  ResponseInterface                   $response
     * @return array
     */
    public function beforeRenderResult(
        \Magento\Framework\View\Result\Page $subject,
        ResponseInterface $response
    ) {
        if ($this->_yotpoHelper->isEnabled() && in_array($this->_context->getRequest()->getFullActionName(), ['catalog_category_view', 'catalog_product_view', 'cms_index_index'])) {
            $subject->getConfig()->addBodyClass('yotpo-yotpo-is-enabled');
        }
        return [$response];
    }
}
