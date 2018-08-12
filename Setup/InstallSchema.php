<?php
namespace Yotpo\Yotpo\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Innovadeltech\Wishlist\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Install script.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
     protected $_resource;

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $installer->endSetup();

        $table = $installer->getConnection()->newTable(
            $installer->getTable('yotpo_rich_snippets')
        )->addColumn(
                'rich_snippet_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true),
                'Id'
            )->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                array('nullable' => false),
                'Product Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                array('nullable' => false),
                'Store Id'
            )->addColumn(
                'average_score',
                \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                null,
                array('nullable' => false),
                'Average Score'
            )->addColumn(
                'reviews_count',
                \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                null,
                array('nullable' => false),
                'Reviews Count'
            )->addColumn(
                'expiration_time',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                array('nullable' => false),
                'Expiry Time'
            );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}