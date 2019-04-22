<?php

namespace Yotpo\Yotpo\Block\Adminhtml\Report;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Yotpo\Yotpo\Helper\ApiClient as YotpoApiClient;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

class Reviews extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::report/reviews.phtml';

    /**
     * @var array
     */
    protected $_totals = [];

    /**
     * @var array
     */
    protected $_allStoreIds = null;

    /**
     * @var OrderCollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoApiClient
     */
    protected $_yotpoApi;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  OrderCollectionFactory $collectionFactory
     * @param  YotpoHelper            $yotpoHelper
     * @param  YotpoApiClient         $yotpoApi
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $collectionFactory,
        YotpoHelper $yotpoHelper,
        YotpoApiClient $yotpoApi,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoApi = $yotpoApi;
        parent::__construct($context, $data);
    }

    public function getStoreIds()
    {
        if (is_null($this->_allStoreIds)) {
            if (($_storeId = $this->getRequest()->getParam("store", 0))) {
                $stores = [$_storeId];
            } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
                $stores = $this->_yotpoHelper->getStoreManager()->getWebsite($websiteId)->getStoreIds();
            } elseif (($groupId = $this->getRequest()->getParam("group", 0))) {
                $stores = $this->_yotpoHelper->getStoreManager()->getGroup($groupId)->getStoreIds();
            } else {
                $stores = $this->_yotpoHelper->getAllStoreIds(true);
            }
            $this->_allStoreIds = array_values($stores);
        }
        return $this->_allStoreIds;
    }

    public function getAppKey()
    {
        if (($storeId = $this->getRequest()->getParam("store", 0))) {
            $appKey = $this->_yotpoHelper->getAppKey($storeId, ScopeInterface::SCOPE_STORE);
        } elseif (($websiteId = $this->getRequest()->getParam("website", 0))) {
            $appKey = $this->_yotpoHelper->getAppKey($websiteId, ScopeInterface::SCOPE_WEBSITE);
        } else {
            $appKey = $this->_yotpoHelper->getAppKey();
        }
        if (!$appKey) {
            $appKey = $this->_yotpoHelper->getAppKey($this->getStoreIds()[0]);
        }
        return $appKey;
    }
    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->_totals;
    }

    /**
     * @method addTotal
     * @param  string   $label
     * @param  mixed    $value
     * @param  string   $class
     */
    public function addTotal($label, $value, string $class = "")
    {
        $this->_totals[] = ['label' => $label, 'value' => $value, 'class' => $class];
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

        $storeIds = $this->getStoreIds();
        $dateRange = $this->_collectionFactory->create()->getDateRange($this->getRequest()->getParam('period', '24h'), 0, 0, true);

        $metrics = $this->_yotpoApi->getMetrics(
            $this->getStoreIds(),
            $dateRange[0]->format(DateTime::DATETIME_PHP_FORMAT),
            $dateRange[1]->format(DateTime::DATETIME_PHP_FORMAT)
        );

        $this->addTotal(__('Emails Sent'), $metrics->emails_sent . 'K', 'yotpo-totals-emails-sent');
        $this->addTotal(__('Avg. Star Rating'), $metrics->star_rating, 'yotpo-totals-star-rating');
        $this->addTotal(__('Collected Reviews'), $metrics->total_reviews, 'yotpo-totals-total-reviews');
        $this->addTotal(__('Collected Photos'), $metrics->photos_generated, 'yotpo-totals-photos-generated');
        $this->addTotal(__('Published Reviews'), $metrics->published_reviews, 'yotpo-totals-published-reviews');
        $this->addTotal(__('Engagement Rate'), $metrics->engagement_rate . '%', 'yotpo-totals-engagement-rate');
    }

    /**
     * Generate yotpo button html
     *
     * @return string
     */
    public function getLounchYotpoButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
            'id' => 'launch_yotpo_button',
            'class' => 'launch-yotpo-button',
            ]
        );
        if (!($appKey = $this->getAppKey())) {
            $button->setLabel(__('Get Started') . ' >');
            $button->setOnClick("window.open('https://www.yotpo.com/integrations/magento/','_blank');");
        } else {
            $button->setLabel(__('Launch Yotpo') . ' >');
            $button->setOnClick("window.open('https://yap.yotpo.com/#/login?preferredAppKey={$appKey}','_blank');");
        }

        return $button->toHtml();
    }

    /**
     * Get url for Yotpo configuration
     *
     * @return string
     */
    public function getYotpoConfigUrl()
    {
        return $this->_urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            [
                'section' => 'yotpo'
            ]
        );
    }
}
