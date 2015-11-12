<?php

namespace Yotpo\Yotpo\Block;
use Magento\Store\Model\ScopeInterface;
class Config
{
    const YOTPO_APP_KEY = 'yotpo/settings/app_key';
    const YOTPO_SECRET = 'yotpo/settings/secret';
    const YOTPO_WIDGET_ENABLED = 'yotpo/settings/widget_enabled';
    const YOTPO_BOTTOMLINE_ENABLED = 'yotpo/settings/bottomline_enabled';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {        
        $this->_scopeConfig = $scopeConfig;
    }

    public function getAppKey()
    {   
        return $this->_scopeConfig->getValue(self::YOTPO_APP_KEY, ScopeInterface::SCOPE_STORE);     
    }

    public function getSecret()
    {        
        return $this->_scopeConfig->getValue(self::YOTPO_SECRET, ScopeInterface::SCOPE_STORE);
    }

    public function getCostumeOrderStatus()
    {
        null;
    }

    public function isWidgetEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_WIDGET_ENABLED, ScopeInterface::SCOPE_STORE);
    } 

    public function isBottomlineEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_BOTTOMLINE_ENABLED, ScopeInterface::SCOPE_STORE);
    } 

    public function isAppKeyAndSecretSet()
    {        
        return ($this->getAppKey() != null && $this->getSecret() != null);
    } 

    public function getTimeFrame()
    {        
        $today = time();
        $last = $today - (60*60*24*90); //90 days ago
        return date("Y-m-d", $last);
    }              
}
