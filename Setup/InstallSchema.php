<?php

namespace Yotpo\Yotpo\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 *
 * @package Innovadeltech\Wishlist\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @method install
     * @param  SchemaSetupInterface   $setup
     * @param  ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $salesConnection = $installer->getConnection('sales');

        // Install table: yotpo_rich_snippets
        $yotpoRichSnippetsTable = $installer->getConnection()->newTable(
            $installer->getTable('yotpo_rich_snippets')
        )->addColumn(
            'rich_snippet_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Product Id'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Store Id'
        )->addColumn(
            'average_score',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '10,2',
            ['nullable' => false],
            'Average Score'
        )->addColumn(
            'reviews_count',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            null,
            ['nullable' => false],
            'Reviews Count'
        )->addColumn(
            'expiration_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false],
            'Expiry Time'
        );
        $installer->getConnection()->createTable($yotpoRichSnippetsTable);

        // Install table: yotpo_order_status_history
        $yotpoOrderStatusHistoryTable = $salesConnection->newTable(
            $installer->getTable('yotpo_order_status_history')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Order Id'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Store Id'
        )->addColumn(
            'old_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => true],
            'Old Status'
        )->addColumn(
            'new_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => true],
            'New Status'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Created At'
        );
        $salesConnection->createTable($yotpoOrderStatusHistoryTable);

        $installer->endSetup();
    }
}
