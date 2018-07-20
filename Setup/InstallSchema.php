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
        
//                $shouldShowNotification = false;
//
//        foreach (Mage::app()->getStores() as $store) {
//            if (!Mage::getStoreConfig('yotpo/yotpo_general_group/yotpo_appkey', $store)) {
//                $shouldShowNotification = true;
//                break;
//            }
//        }
//
//        #handle single store magento site
//        if (!Mage::getStoreConfig('yotpo/yotpo_general_group/yotpo_appkey', Mage::app()->getStore())) {
//            $shouldShowNotification = true;
//        }
//
//        if ($shouldShowNotification) {
//            Mage::helper('yotpo/Utils')->createAdminNotification
//            (
//                "Please visit the Yotpo extension page in your system configuration store settings page and finish the installation.",
//                "In order to start generating reviews with Yotpo, you'll need to finish the installation process",
//                "http://support.yotpo.com/entries/24858236-Configuring-Yotpo-after-installation?utm_source=customers_magento_admin&utm_medium=pop_up&utm_campaign=magento_not_installed_pop_up"
//            );
//        }
    }
}