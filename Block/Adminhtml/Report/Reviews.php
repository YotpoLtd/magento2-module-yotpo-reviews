<?php

namespace Yotpo\Yotpo\Block\Adminhtml\Report;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Yotpo\Model\Api\AccountUsages as YotpoApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class Reviews extends \Magento\Backend\Block\Template
{
    /**
     * initialize:
     */
    private $initialized;
    private $scope = ScopeInterface::SCOPE_STORE;
    private $scopeId;
    private $isEnabled;
    private $appKey;
    private $isAppKeyAndSecretSet;
    private $allStoreIds;

    /**
     * @var array
     */
    private $totals = [];

    /**
     * @var string
     */
    protected $_defaultPeriod = '30d';

    /**
     * @var string
     */
    protected $_template = 'Yotpo_Yotpo::report/reviews.phtml';

    /**
     * @var OrderCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * @method __construct
     * @param  Context                $context
     * @param  OrderCollectionFactory $collectionFactory
     * @param  YotpoConfig            $yotpoConfig
     * @param  YotpoApi               $yotpoApi
     * @param  array                  $data
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $collectionFactory,
        YotpoConfig $yotpoConfig,
        YotpoApi $yotpoApi,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->yotpoConfig = $yotpoConfig;
        $this->yotpoApi = $yotpoApi;
        parent::__construct($context, $data);
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        if (($storeId = $this->getRequest()->getParam(ScopeInterface::SCOPE_STORE, 0))) {
            $this->allStoreIds = [$storeId];
        } elseif (($websiteId = $this->getRequest()->getParam(ScopeInterface::SCOPE_WEBSITE, 0))) {
            $this->allStoreIds = $this->yotpoConfig->getStoreManager()->getWebsite($websiteId)->getStoreIds();
        } else {
            $this->allStoreIds = $this->yotpoConfig->getAllStoreIds(false);
        }
        $this->allStoreIds = $this->yotpoConfig->filterDisabledStoreIds($this->allStoreIds);
        $this->scopeId = ($this->allStoreIds) ? $this->allStoreIds[0] : null;

        $this->isEnabled = ($this->allStoreIds) ? true : false;
        $this->isAppKeyAndSecretSet = ($this->allStoreIds) ? true : false;
        $this->appKey = ($this->scopeId) ? $this->yotpoConfig->getAppKey($this->scopeId, $this->scope) : null;
    }

    public function isEnabledAndConfigured()
    {
        return ($this->isEnabled && $this->isAppKeyAndSecretSet) ? true : false;
    }

    public function getStoreIds()
    {
        return $this->allStoreIds;
    }

    public function getPeriod($default = null)
    {
        return $this->getRequest()->getParam('period', ($default ?: $this->_defaultPeriod));
    }

    public function isEnabled()
    {
        return $this->isEnabled;
    }

    public function getAppKey()
    {
        return $this->appKey;
    }

    public function isAppKeyAndSecretSet()
    {
        return $this->isAppKeyAndSecretSet;
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->totals;
    }

    /**
     * @method addTotal
     * @param  string   $label
     * @param  mixed    $value
     * @param  string   $class
     */
    private function addTotal($label, $value, $class = "")
    {
        $this->totals[] = ['label' => $label, 'value' => $value, 'class' => $class];
        return $this;
    }

    /**
     * Calculate From and To dates (or times) by given period
     *
     * @param string $range
     * @param string $customStart
     * @param string $customEnd
     * @param bool $returnObjects
     * @return array
     */
    private function getDateRange($range, $customStart, $customEnd, $returnObjects = false)
    {
        $dateEnd = new \DateTime();
        $dateStart = new \DateTime();

        // go to the end of a day
        //$dateEnd->setTime(23, 59, 59);
        //$dateStart->setTime(0, 0, 0);

        switch ($range) {
            case '24h':
                $dateEnd = new \DateTime();
                $dateEnd->modify('+1 hour');
                $dateStart = clone $dateEnd;
                $dateStart->modify('-1 day');
                break;
            case '1d':
                $dateStart->modify('-1 days');
                break;

            case '7d':
                $dateStart->modify('-6 days');
                break;

            case '30d':
                $dateStart->modify('-30 days');
                break;

            case '1m':
                $dateStart->setDate(
                    $dateStart->format('Y'),
                    $dateStart->format('m'),
                    $this->yotpoConfig->getConfig('reports/dashboard/mtd_start')
                );
                break;

            case 'custom':
                $dateStart = $customStart ? $customStart : $dateEnd;
                $dateEnd = $customEnd ? $customEnd : $dateEnd;
                break;

            case '1y':
            case '2y':
                $startMonthDay = explode(
                    ',',
                    $this->yotpoConfig->getConfig('reports/dashboard/ytd_start')
                );
                $startMonth = isset($startMonthDay[0]) ? (int)$startMonthDay[0] : 1;
                $startDay = isset($startMonthDay[1]) ? (int)$startMonthDay[1] : 1;
                $dateStart->setDate($dateStart->format('Y'), $startMonth, $startDay);
                if ($range == '2y') {
                    $dateStart->modify('-1 year');
                }
                break;

            case 'all':
                $dateStart->modify('-1000 years');
                break;
        }

        if ($returnObjects) {
            return [$dateStart, $dateEnd];
        } else {
            return ['from' => $dateStart, 'to' => $dateEnd, 'datetime' => true];
        }
    }

    /**
     * @return $this|void
     */
    protected function _prepareLayout()
    {
        $this->initialize();
        $storeIds = $this->getStoreIds();
        $dateRange = $this->getDateRange($this->getPeriod(), 0, 0, true);

        $metrics = $this->yotpoApi->getMetrics(
            $this->getStoreIds(),
            $dateRange[0]->format(DateTime::DATETIME_PHP_FORMAT),
            $dateRange[1]->format(DateTime::DATETIME_PHP_FORMAT)
        );

        if (!isset($metrics['emails_sent'])) {
            $emailsSent = "-";
        } elseif ($metrics['emails_sent'] > 999999) {
            $emailsSent = number_format((float)($metrics['emails_sent']/1000000), 1, '.', "") . 'M';
        } elseif ($metrics['emails_sent'] > 99999) {
            $emailsSent = number_format((float)($metrics['emails_sent']/1000), 0, '.', "") . 'K';
        } elseif ($metrics['emails_sent'] > 999) {
            $emailsSent = number_format((float)($metrics['emails_sent']/1000), 1, '.', "") . 'K';
        } else {
            $emailsSent = round((float)$metrics['emails_sent'], 2);
        }

        $this->addTotal(__('Emails Sent'), $emailsSent, 'yotpo-totals-emails-sent');
        $this->addTotal(__('Avg. Star Rating'), (isset($metrics['star_rating'])) ? round((float)$metrics['star_rating'], 2) : '-', 'yotpo-totals-star-rating');
        $this->addTotal(__('Collected Reviews'), (isset($metrics['total_reviews'])) ? round((float)$metrics['total_reviews'], 2) : '-', 'yotpo-totals-total-reviews');
        $this->addTotal(__('Collected Photos'), (isset($metrics['photos_generated'])) ? round((float)$metrics['photos_generated'], 2) : '-', 'yotpo-totals-photos-generated');
        $this->addTotal(__('Published Reviews'), (isset($metrics['published_reviews'])) ? round((float)$metrics['published_reviews'], 2) : '-', 'yotpo-totals-published-reviews');
        $this->addTotal(__('Engagement Rate'), (isset($metrics['engagement_rate'])) ? round((float)$metrics['engagement_rate'], 2) . '%' : '-', 'yotpo-totals-engagement-rate');
    }

    /**
     * Generate yotpo button html
     *
     * @param string $utm
     * @return string
     */
    public function getLounchYotpoButtonHtml($utm = 'MagentoAdmin_Dashboard')
    {
        $this->initialize();
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
            'id' => 'launch_yotpo_button',
            'class' => 'launch-yotpo-button yotpo-cta-add-arrow',
            ]
        );
        if (!($appKey = $this->getAppKey())) {
            $button->setLabel(__('Get Started'));
            $button->setOnClick("window.open('https://www.yotpo.com/integrations/magento/?utm_source={$utm}','_blank');");
        } else {
            $button->setLabel(__('Launch Yotpo'));
            $button->setOnClick("window.open('https://yap.yotpo.com/#/preferredAppKey={$appKey}','_blank');");
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
        $this->initialize();
        $params = ['section' => 'yotpo'];
        if ($this->scope) {
            $params[$this->scope] = $this->scopeId;
        }
        return $this->_urlBuilder->getUrl('adminhtml/system_config/edit', $params);
    }

    public function getPeriods()
    {
        return [
            '1d' => 'Last Day',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            'all' => 'All Time',
        ];
    }
}
