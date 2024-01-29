<?php

namespace Category\Product\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('category_product')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('category_product')
			)
				->addColumn(
					'entity_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'Entity ID'
				)
				->addColumn(
					'assigned_product',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'64k',
					[],
					'Assigned Products'
				)
				->addColumn(
					'product_recurisive',
					\Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
					'64k',
					[],
					'product Recurisive'
				)
				->addColumn(
					'selected_category',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					255,
					[],
					'Selected Category'
				)
				->addColumn(
					'checked_category',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Checked Category'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updated At')
				->setComment('Post Table');
			$installer->getConnection()->createTable($table);
		}
		$installer->endSetup();
	}
}