<?php

namespace Yotpo\Yotpo\Block;
use Magento\Store\Model\ScopeInterface;
class Config
{
    const YOTPO_APP_KEY = 'yotpo/settings/app_key';
    const YOTPO_SECRET = 'yotpo/settings/secret';
    const YOTPO_SHOW_WIDGET = 'yotpo/settings/show_widget';

    protected $scopeConfig;

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
        return (bool)$this->scopeConfig->getValue(self::YOTPO_SHOW_WIDGET, ScopeInterface::SCOPE_STORE);
    } 

    public function isAppKeyAndSecretSet()
    {        
        return ($this->getAppKey() != null && $this->getSecret() != null);
    }             
}
