<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

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
     protected $_resourceConfig;

     /**
      * @var DateTimeFactory
      */
     protected $_datetimeFactory;

     /**
      * Application config
      *
      * @var ScopeConfigInterface
      */
     protected $_appConfig;

     /**
      * @method __construct
      * @param  ResourceConfig $resourceConfig
      * @param  DateTimeFactory $datetimeFactory
      * @param  ReinitableConfigInterface $appConfig
      */
     public function __construct(
        ResourceConfig $resourceConfig,
        DateTimeFactory $datetimeFactory,
        ReinitableConfigInterface $appConfig
    ) {
         $this->_resourceConfig = $resourceConfig;
         $this->_datetimeFactory = $datetimeFactory;
         $this->_appConfig = $appConfig;
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
             $syncTable = $installer->getConnection()->newTable(
                $installer->getTable('yotpo_sync')
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
             $installer->getConnection()->createTable($syncTable);

             $currentDate = $this->_datetimeFactory->create()->gmtDate('Y-m-d');
             $this->_resourceConfig->saveConfig(YotpoHelper::XML_PATH_YOTPO_MODULE_INFO_INSTALLATION_DATE, $currentDate, 'default', 0);
             $this->_resourceConfig->saveConfig(YotpoHelper::XML_PATH_YOTPO_ORDERS_SYNC_FROM_DATE, $currentDate, 'default', 0);
             $this->_appConfig->reinit();
         }

         $installer->endSetup();
     }
 }
