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
            foreach ($this->yotpoConfig->getAllStoreIds(false, false) as $key => $storeId) {
                $isEnabled = $this->yotpoConfig->isEnabled($storeId, ScopeInterface::SCOPE_STORE);
                $appKey = $this->yotpoConfig->getAppKey($storeId, ScopeInterface::SCOPE_STORE);
                $secret = $this->yotpoConfig->getSecret($storeId, ScopeInterface::SCOPE_STORE);

                if (!$appKey || !$secret) {
                    $this->yotpoConfig->resetStoreCredentials($storeId);
                    continue;
                }
                if (!isset($appKeysStores[$appKey])) {
                    $appKeysStores[$appKey] = [];
                    $this->yotpoConfig->setStoreCredentialsAndIsEnabled($appKey, $secret, $isEnabled, $storeId);
                }
                $appKeysStores[$appKey][] = $storeId;
            }

            // Clear config values for the 'default' & 'website' scopes
            $setup->getConnection()->delete(
                $setup->getTable('core_config_data'),
                "path IN ('" . implode("','", [YotpoConfig::XML_PATH_YOTPO_APP_KEY, YotpoConfig::XML_PATH_YOTPO_SECRET, YotpoConfig::XML_PATH_YOTPO_ENABLED]) . "') AND scope != '" . ScopeInterface::SCOPE_STORES . "'"
            );

            //Remove appKey duplications
            $resetStores = [];
            foreach ($appKeysStores as $_appkey => $stores) {
                if (count($stores) > 1) {
                    foreach ($stores as $storeId) {
                        $this->yotpoConfig->resetStoreCredentials($storeId);
                        $resetStores[] = $storeId;
                    }
                }
            }
            $resetStoresMsg = '';
            if ($resetStores) {
                $resetStoresMsg = ' *Note that Yotpo requires unique set of credentials for each store. As we have detected duplicate or invalid credentials, your Yotpo credentials have been reset for the following stores: ' . implode(",", $resetStores) . '. Copy and paste the Yotpo credentials of the aforementioned store(s) within the relevant Store View scope settings.';
                $this->output->writeln("<comment>' . $resetStoresMsg . '</comment>");
            }

            if ($context->getVersion()) {
                $this->addAdminNotification("Important message from Yotpo regarding the configuration scopes & multi-store support... (module: Yotpo_Yotpo)", "Note that Yotpo can only be connected and enabled/disabled via the Store View scope. As part of the latest module upgrade, your currently defined values were saved to your default store scope, and your default and website scope configurations were reset. \n Please verify that your Yotpo credentials are correct and connected to the right stores" . $resetStoresMsg);
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
