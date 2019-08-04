<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Upgrade Data script
 *
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var NotifierInterface
     */
    private $notifierPool;

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @method __construct
     * @param  ReinitableConfigInterface $appConfig
     * @param  YotpoConfig               $yotpoConfig
     * @param  NotifierInterface         $notifierPool
     * @param  ConsoleOutput             $output
     */
    public function __construct(
        YotpoConfig $yotpoConfig,
        NotifierInterface $notifierPool,
        ConsoleOutput $output
    ) {
        $this->yotpoConfig = $yotpoConfig;
        $this->notifierPool = $notifierPool;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($context->getVersion() && version_compare($context->getVersion(), '2.9.0', '<')) {
            $this->output->writeln("<comment>Reseting configurations for 'default' & 'website' scopes (only supports 'store' at the moment)</comment>");

            $appKeysStores = [];

            //Move all configurations to the 'store' scope.
            foreach ($this->yotpoConfig->getAllStoreIds(false) as $key => $storeId) {
                $isEnabled = $this->yotpoConfig->isEnabled($storeId, ScopeInterface::SCOPE_STORE);
                $appKey = $this->yotpoConfig->getAppKey($storeId, ScopeInterface::SCOPE_STORE);
                $secret = $this->yotpoConfig->getSecret($storeId, ScopeInterface::SCOPE_STORE);

                if (!isset($appKeysStores[$appKey])) {
                    $this->yotpoConfig->setStoreCredentialsAndIsEnabled($appKey, $secret, $isEnabled, $storeId);
                } else {
                    $appKeysStores[$appKey] = [];
                }
                $appKeysStores[$appKey][] = $storeId;
            }

            // Clear config values for the 'default' & 'website' scopes
            $setup->getConnection()->delete(
                $coreConfigTable,
                "path IN ('" . implode("','", [YotpoConfig::XML_PATH_YOTPO_APP_KEY, YotpoConfig::XML_PATH_YOTPO_SECRET, YotpoConfig::XML_PATH_YOTPO_ENABLED]) . "') AND scope != '" . ScopeInterface::SCOPE_STORES . "'"
            );

            //Remove appKey duplications
            $resetStores = [];
            foreach ($appKeysStores as $_appkey => $stores) {
                if (count($stores) > 1) {
                    foreach ($appKeysStores as $storeId) {
                        $this->yotpoConfig->resetStoreCredentials($storeId);
                        $resetStores[] = $storeId;
                    }
                }
            }
            $resetStoresMsg = '';
            if ($resetStores) {
                $resetStoresMsg = ' *Note that we also reset the credentials on store IDs: ' . implode(",", $resetStores) . ' since they had duplicated credentials & Yotpo requires a unique set of app-key & secret for each store.';
                $this->output->writeln("<comment>' . $resetStoresMsg . '</comment>");
            }

            if ($context->getVersion()) {
                $this->addAdminNotification("Important message from Yotpo regarding the configuration scopes & multi-store support... (module: Yotpo_Yotpo)", "Yotpo can only be connected, enabled, or disabled on the Store View scope. During the last module upgrade we reset the configurations for the 'default' & 'website' scopes & saved all current values on the 'store' scope." . $resetStoresMsg);
            }
        }

        $setup->endSetup();
    }

    private function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        $method = 'add' . ucfirst($type);
        $this->notifierPool->{$method}($title, $description);
        return $this;
    }
}
