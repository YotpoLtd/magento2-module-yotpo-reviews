<?php

namespace Yotpo\Yotpo\Observer\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Listen for Single Store Mode change and adjust configuration.
 */
class SingleStoreModeSwitch implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param YotpoConfig $yotpoConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        YotpoConfig $yotpoConfig
    ) {
        $this->storeManager = $storeManager;
        $this->yotpoConfig = $yotpoConfig;
    }

    /**
     * Modify config if single store mode switched.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getChangedPaths();
        if (!in_array(StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED, $changedPaths)) {
            return;
        }

        if ($this->storeManager->isSingleStoreMode()) {
            $this->migrateToSingleStoreMode();
        } else {
            $this->migrateFromSingleStoreMode();
        }
    }

    /**
     * Use default store view config as default config.
     */
    private function migrateToSingleStoreMode()
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        $this->moveConfigValues(
            ['scope' => ScopeInterface::SCOPE_STORES, 'id' => $defaultStoreView->getId()],
            ['scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 'id' => 0]
        );
    }

    /**
     * Use default config as default store view config.
     */
    private function migrateFromSingleStoreMode()
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        $this->moveConfigValues(
            ['scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 'id' => 0],
            ['scope' => ScopeInterface::SCOPE_STORES, 'id' => $defaultStoreView->getId()]
        );
    }

    /**
     * Move Yotpo setup configuration from one scope to another.
     *
     * @param array $from
     * @param array $to
     */
    private function moveConfigValues($from, $to)
    {
        $appKey = $this->yotpoConfig->getAppKey($from['id'], $from['scope']);
        $apiSecret = $this->yotpoConfig->getSecret($from['id'], $from['scope']);
        $enabled = $this->yotpoConfig->isEnabled($from['id'], $from['scope']);
        if ($appKey && $apiSecret) {
            $this->yotpoConfig->setStoreCredentialsAndIsEnabled($appKey, $apiSecret, $enabled ? '1' : '0', $to['id'], $to['scope']);
        } else {
            $this->yotpoConfig->resetStoreCredentials($to['id'], $to['scope']);
        }

        $this->yotpoConfig->resetStoreCredentials($from['id'], $from['scope']);
    }
}
