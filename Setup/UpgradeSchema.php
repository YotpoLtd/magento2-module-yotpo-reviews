<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

/**
 * Class UpgradeSchema
 *
 * @package Innovadeltech\Wishlist\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
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
     * @method __construct
     * @param  ResourceConfig            $resourceConfig
     * @param  DateTimeFactory           $datetimeFactory
     * @param  ReinitableConfigInterface $appConfig
     * @param  YotpoConfig               $yotpoConfig
     * @param  NotifierInterface         $notifierPool
     */
    public function __construct(
        ResourceConfig $resourceConfig,
        DateTimeFactory $datetimeFactory,
        ReinitableConfigInterface $appConfig,
        YotpoConfig $yotpoConfig,
        NotifierInterface $notifierPool
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->datetimeFactory = $datetimeFactory;
        $this->appConfig = $appConfig;
        $this->yotpoConfig = $yotpoConfig;
        $this->notifierPool = $notifierPool;
    }

    /**
     * @method upgrade
     * @param  SchemaSetupInterface   $setup
     * @param  ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.7.5', '<')) {
            $currentDate = $this->datetimeFactory->create()->gmtDate('Y-m-d');
            $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE, $currentDate, 'default', 0);
            $this->resourceConfig->saveConfig(YotpoConfig::XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE, $currentDate, 'default', 0);
            $this->appConfig->reinit();
        }

        $salesConnection = $installer->getConnection('sales');
        $fullTableName = $installer->getTable('yotpo_sync');
        if (!$salesConnection->isTableExists($fullTableName)) {
            $defaultConnection = $installer->getConnection();
            $withDataMigration = $defaultConnection->isTableExists($fullTableName);

            $syncTable = $salesConnection->newTable(
                $fullTableName
            )->addColumn(
                'sync_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Store ID'
            )
            ->addColumn(
                'entity_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => true],
                'Entity Type'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Entity ID'
            )->addColumn(
                'sync_flag',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => true, 'default' => '0'],
                'Sync Flag'
            )->addColumn(
                'sync_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Sync Date'
            )->addIndex(
                $setup->getIdxName(
                    'yotpo_sync',
                    ['store_id', 'entity_type', 'entity_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['store_id', 'entity_type', 'entity_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );
            $salesConnection->createTable($syncTable);

            if ($withDataMigration) {
                $maxBatchSize = 100;

                do {
                    $selectFromOldTable = $defaultConnection->select()->from($fullTableName)->limit($maxBatchSize);
                    $existingData = $defaultConnection->query($selectFromOldTable)->fetchAll();
                    $batchSize = count($existingData);
                    if ($batchSize) {
                        $columns = array_keys($existingData[0]);
                        $salesConnection->insertArray($fullTableName, $columns, $existingData);
                    }
                } while ($batchSize === $maxBatchSize);
                $defaultConnection->dropTable($fullTableName);
            }
        }

        $installer->endSetup();
    }
}
