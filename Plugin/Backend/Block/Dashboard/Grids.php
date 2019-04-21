<?php

namespace Yotpo\Yotpo\Plugin\Backend\Block\Dashboard;

use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Plugin for Grids
 */
class Grids
{

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    public function __construct(
        YotpoHelper $yotpoHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;
    }

    /**
     * @method beforeRenderResult
     * @param  \Magento\Backend\Block\Dashboard\Grids $subject
     * @return array
     */
    public function beforeToHtml(
        \Magento\Backend\Block\Dashboard\Grids $subject
    ) {
        if ($this->_yotpoHelper->isEnabled()) {
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
