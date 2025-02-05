<?php

namespace EffectConnect\Marketplaces\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package EffectConnect\Marketplaces\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer  = $setup;

        $installer->startSetup();
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), "1.0.25", "<")) {
            $this->addChannelMappingStoreViewIds($setup, $installer, $connection);
        }

        if (version_compare($context->getVersion(), "1.0.28", "<")) {
            $this->addConnectionBaseStoreViewId($setup, $installer, $connection);
        }

        if (version_compare($context->getVersion(), "1.0.36", "<")) {
            $this->addExportedFieldToOrderLines($setup, $installer, $connection);
        }

        if (version_compare($context->getVersion(), "1.0.41", "<")) {
            $this->increaseEcOrderLineIdLength($setup, $installer, $connection);
        }

        $installer->endSetup();
    }

    /**
     * Add storeview_id_internal and storeview_id_external to ec_marketplaces_channel_mapping.
     *
     * @param SchemaSetupInterface $setup
     * @param SchemaSetupInterface $installer
     * @param AdapterInterface $connection
     */
    protected function addChannelMappingStoreViewIds(SchemaSetupInterface $setup, SchemaSetupInterface $installer, AdapterInterface $connection)
    {
        $tableName = $setup->getTable('ec_marketplaces_channel_mapping');
        if ($connection->isTableExists($tableName) == true)
        {
            $connection->addColumn(
                $tableName,
                'storeview_id_internal',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'unsigned' => true,
                    'default'  => null,
                    'comment'  => 'Storeview ID for internal orders (foreign key to: store.store_id)',
                ]
            );

            $connection->addColumn(
                $tableName,
                'storeview_id_external',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'unsigned' => true,
                    'default'  => null,
                    'comment'  => 'Storeview ID for external orders (foreign key to: store.store_id)',
                ]
            );

            $connection->addForeignKey(
                $installer->getFkName('ec_marketplaces_channel_mapping', 'storeview_id_internal', 'store', 'store_id'),
                $tableName,
                'storeview_id_internal',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_SET_NULL
            );

            $connection->addForeignKey(
                $installer->getFkName('ec_marketplaces_channel_mapping', 'storeview_id_external', 'store', 'store_id'),
                $tableName,
                'storeview_id_external',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_SET_NULL
            );
        }
    }

    /**
     * Add base_storeview_id to ec_marketplaces_connection.
     *
     * @param SchemaSetupInterface $setup
     * @param SchemaSetupInterface $installer
     * @param AdapterInterface $connection
     */
    protected function addConnectionBaseStoreViewId(SchemaSetupInterface $setup, SchemaSetupInterface $installer, AdapterInterface $connection)
    {
        $tableName = $setup->getTable('ec_marketplaces_connection');
        if ($connection->isTableExists($tableName) == true)
        {
            $connection->addColumn(
                $tableName,
                'base_storeview_id',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => 0,
                    'comment'  => 'Storeview ID to get the base information from (foreign key to: store.store_id).',
                ]
            );

            $connection->addForeignKey(
                $installer->getFkName('ec_marketplaces_connection', 'base_storeview_id', 'store', 'store_id'),
                $tableName,
                'base_storeview_id',
                $installer->getTable('store'),
                'store_id',
                Table::ACTION_SET_DEFAULT
            );
        }
    }

    /**
     * Add export field to ec_marketplaces_order_lines table.
     *
     * @param SchemaSetupInterface $setup
     * @param SchemaSetupInterface $installer
     * @param AdapterInterface $connection
     */
    protected function addExportedFieldToOrderLines(SchemaSetupInterface $setup, SchemaSetupInterface $installer, AdapterInterface $connection)
    {
        $tableName = $setup->getTable('ec_marketplaces_order_lines');
        if ($connection->isTableExists($tableName) == true)
        {
            $connection->addColumn(
                $tableName,
                'export',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'unsigned' => true,
                    'default'  => 0,
                    'comment'  => 'Whether the order line is ready for export.',
                ]
            );
        }
    }

    /**
     * Increase varchar length of ec_marketplaces_order_lines.ec_order_line_id from 64 to 128.
     *
     * @param SchemaSetupInterface $setup
     * @param SchemaSetupInterface $installer
     * @param AdapterInterface $connection
     * @return void
     */
    protected function increaseEcOrderLineIdLength(SchemaSetupInterface $setup, SchemaSetupInterface $installer, AdapterInterface $connection)
    {
        $tableName = $setup->getTable('ec_marketplaces_order_lines');
        if ($connection->tableColumnExists($tableName, 'ec_order_line_id')) {
            $connection->modifyColumn(
                $tableName,
                'ec_order_line_id',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 128,
                    'nullable' => false,
                    'comment'  => 'EffectConnect Order Line ID',
                ]
            );
        }
    }
}
