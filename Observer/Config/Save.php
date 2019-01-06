<?php

namespace Yotpo\Yotpo\Observer\Config;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Save implements ObserverInterface
{
    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    protected $_appConfig;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @param TypeListInterface         $cacheTypeList
     * @param ReinitableConfigInterface $config
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config
    ) {
        $this->_cacheTypeList = $cacheTypeList;
        $this->_appConfig = $config;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($observer->getEvent()->getChangedPaths()) {
            $this->_cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->_appConfig->reinit();
        }
    }
}
