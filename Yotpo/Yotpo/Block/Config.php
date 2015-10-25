<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Manage currency symbols block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Yotpo\Yotpo\Block;
use Magento\Store\Model\ScopeInterface;
class Config
{
    const YOTPO_APP_KEY = 'yotpo/settings/app_key';
    const YOTPO_SECRET = 'yotpo/settings/secret';
    const YOTPO_SHOW_WIDGET = 'yotpo/settings/show_widget';

    protected $scopeConfig;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {        
        $this->scopeConfig = $scopeConfig;
    }

    public function getAppKey()
    {   
        return $this->scopeConfig->getValue(self::YOTPO_APP_KEY, ScopeInterface::SCOPE_STORE);     
    }

    public function getSecret()
    {        
        return $this->scopeConfig->getValue(self::YOTPO_SECRET, ScopeInterface::SCOPE_STORE);
    }

    public function getShowWidget()
    {        
        return $this->scopeConfig->getValue(self::YOTPO_SHOW_WIDGET, ScopeInterface::SCOPE_STORE);
    }         
}
