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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\Resource\Config $resourceConfig
    ) {        
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConfig = $resourceConfig;
    }

    public function getAppKey()
    {   
        return $this->_scopeConfig->getValue(self::YOTPO_APP_KEY, ScopeInterface::SCOPE_STORE, 1);     
    }

    public function getSecret()
    {        
        return $this->_scopeConfig->getValue(self::YOTPO_SECRET, ScopeInterface::SCOPE_STORE, 1);
    }

    public function isWidgetEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_WIDGET_ENABLED, ScopeInterface::SCOPE_STORE, 1);
    } 

    public function isBottomlineEnabled()
    {        
        return (bool)$this->_scopeConfig->getValue(self::YOTPO_BOTTOMLINE_ENABLED, ScopeInterface::SCOPE_STORE, 1);
    } 

    public function setAppKey($val)
    {   //TODO last parameter should probably be store id
        $this->_resourceConfig->saveConfig(self::YOTPO_APP_KEY, $val, ScopeInterface::SCOPE_STORE, 1);
    }

    public function setSecret($val)
    {        
        $this->_resourceConfig->saveConfig(self::YOTPO_SECRET, $val, ScopeInterface::SCOPE_STORE, 1);
    }

    public function setWidgetEnabled($val)
    {        
        $this->_resourceConfig->saveConfig(self::YOTPO_WIDGET_ENABLED, $val, ScopeInterface::SCOPE_STORE, 1);
    } 
    
    public function isAppKeyAndSecretSet()
    {        
        return ($this->getAppKey() != null && $this->getSecret() != null);
    }             
}
