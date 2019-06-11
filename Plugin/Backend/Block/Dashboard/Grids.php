<?php

namespace Yotpo\Yotpo\Plugin\Backend\Block\Dashboard;

/**
 * Plugin for Grids
 */
class Grids
{
    /**
     * @method beforeRenderResult
     * @param  \Magento\Backend\Block\Dashboard\Grids $subject
     * @return array
     */
    public function beforeToHtml(
        \Magento\Backend\Block\Dashboard\Grids $subject
    ) {
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
