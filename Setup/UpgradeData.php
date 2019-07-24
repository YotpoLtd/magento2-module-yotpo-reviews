<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
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
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var DateTimeFactory
     */
    private $datetimeFactory;

    /**
     * Application config
     *
     * @var ScopeConfigInterface
     */
    private $appConfig;

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
     * @param  ResourceConfig            $resourceConfig
     * @param  DateTimeFactory           $datetimeFactory
     * @param  ReinitableConfigInterface $appConfig
     * @param  YotpoConfig               $yotpoConfig
     * @param  NotifierInterface         $notifierPool
     * @param  ConsoleOutput             $output
     */
    public function __construct(
        ResourceConfig $resourceConfig,
        DateTimeFactory $datetimeFactory,
        ReinitableConfigInterface $appConfig,
        YotpoConfig $yotpoConfig,
        NotifierInterface $notifierPool,
        ConsoleOutput $output
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->datetimeFactory = $datetimeFactory;
        $this->appConfig = $appConfig;
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

            // Get current default config values:
            $isEnabled = $this->yotpoConfig->isEnabled();
            $appKey = $this->yotpoConfig->getAppKey();
            $secret = $this->yotpoConfig->getSecret();
            $coreConfigTable = $setup->getConnection()->getTableName('core_config_data');

            // Clear config values for the default & website scopes
            $setup->getConnection()->delete(
                $coreConfigTable,
                "path IN ('" . implode("','", [YotpoConfig::XML_PATH_YOTPO_APP_KEY, YotpoConfig::XML_PATH_YOTPO_SECRET, YotpoConfig::XML_PATH_YOTPO_ENABLED]) . "') AND scope != '" . ScopeInterface::SCOPE_STORES . "'"
            );
            $this->appConfig->reinit();

            // Check if the first store has no Yotpo credentials & use the ones
            foreach ($this->yotpoConfig->getAllStoreIds(false) as $key => $storeId) {
                if ($key === 0 && $appKey && $secret) {
                    $_appKeyExists = $setup->getConnection()->fetchAll("SELECT * FROM `" . $coreConfigTable . "` WHERE `path` = '" . YotpoConfig::XML_PATH_YOTPO_APP_KEY . "' AND `scope`='" . ScopeInterface::SCOPE_STORES . "' AND `scope_id`=' . $storeId . ' LIMIT 1");
                    $_secretExists = $setup->getConnection()->fetchAll("SELECT * FROM `" . $coreConfigTable . "` WHERE `path` = '" . YotpoConfig::XML_PATH_YOTPO_SECRET . "' AND `scope`='" . ScopeInterface::SCOPE_STORES . "' AND `scope_id`=' . $storeId . ' LIMIT 1");
                    if (!(count($_appKeyExists) && count($_secretExists))) {
                        $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_APP_KEY, $isEnabled, ScopeInterface::SCOPE_STORES, $storeId);
                        $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_SECRET, $isEnabled, ScopeInterface::SCOPE_STORES, $storeId);
                    }
                    $_isEnabled = $setup->getConnection()->fetchAll("SELECT * FROM `" . $coreConfigTable . "` WHERE `path` = '" . YotpoConfig::XML_PATH_YOTPO_ENABLED . "' AND `scope`='" . ScopeInterface::SCOPE_STORES . "' AND `scope_id`=' . $storeId . ' LIMIT 1");
                    if (!count($_isEnabled)) {
                        $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_ENABLED, $isEnabled, ScopeInterface::SCOPE_STORES, $storeId);
                    }
                } elseif (!$this->yotpoConfig->isAppKeyAndSecretSet($storeId, ScopeInterface::SCOPE_STORE)) {
                    $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_APP_KEY, null, ScopeInterface::SCOPE_STORES, $storeId);
                    $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_SECRET, null, ScopeInterface::SCOPE_STORES, $storeId);
                    $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_APP_KEY, null, ScopeInterface::SCOPE_STORES, $storeId);
                }
            }

            if ($context->getVersion()) {
                $this->addAdminNotification("Important message from Yotpo regarding the configuration scopes & multi-store support... (module: Yotpo_Yotpo)", "Yotpo can only be connected, enabled, or disabled on the Store View scope. During the last module upgrade we reset the configurations for the 'default' & 'website' scopes & copied the current default credentials from the 'default' scope to to the first store (if it was missing).");
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
