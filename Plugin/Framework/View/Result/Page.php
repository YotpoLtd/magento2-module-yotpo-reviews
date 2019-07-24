<?php

namespace Yotpo\Yotpo\Plugin\Framework\View\Result;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Context;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Plugin for Page View
 */
class Page
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    public function __construct(
        Context $context,
        YotpoConfig $yotpoConfig
    ) {
        $this->context = $context;
        $this->yotpoConfig = $yotpoConfig;
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
        if ($this->yotpoConfig->isEnabled() && in_array($this->context->getRequest()->getFullActionName(), ['catalog_category_view', 'catalog_product_view', 'cms_index_index'])) {
            $subject->getConfig()->addBodyClass('yotpo-yotpo-is-enabled');
        }
        return [$response];
    }
}
