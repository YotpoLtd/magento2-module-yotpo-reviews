<?php

namespace Yotpo\Yotpo\Block\Adminhtml\Dashboard\Tab;

use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Adminhtml dashboard Yotpo reviews tab
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class YotpoReviews extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::dashboard/yotpo_reviews_tab.phtml';

    /**
     * @var array
     */
    protected $_totals = [];

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param YotpoHelper $yotpoHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        YotpoHelper $yotpoHelper,
        array $data = []
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('yotpoReviewsTab');
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->_totals;
    }

    /**
     * @param string $label
     * @param float $value
     * @return $this
     */
    public function addTotal($label, $value)
    {
        $this->_totals[] = ['label' => $label, 'value' => $value];
        return $this;
    }

    /**
     * @return $this|void
     */
    protected function _prepareLayout()
    {
        if (!$this->_yotpoHelper->isEnabled()) {
            return $this;
        }
        $isFilter = $this->getRequest()->getParam(
            'store'
        ) || $this->getRequest()->getParam(
            'website'
        ) || $this->getRequest()->getParam(
            'group'
        );
        $period = $this->getRequest()->getParam('period', '24h');

        $this->addTotal(__('Emails Sent'), '1.2K');
        $this->addTotal(__('Collected Reviews'), '704');
        $this->addTotal(__('Published Reviews'), '507');
        $this->addTotal(__('Avg. Star Rating'), '4.2');
        $this->addTotal(__('Collected Photos'), '100');
        $this->addTotal(__('Engagement Rate'), '89%');
    }
}
