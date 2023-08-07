<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class ChangeProductScheduleConfigPaths
 * used to change system configuration paths.
 */
class ChangeProductScheduleConfigPaths implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $paths = [
            'url_rewrite_generator/product_schedule_config/is_active' =>
                'url_rewrite_generator/product_entity_config/enable_schedule',
            'url_rewrite_generator/product_schedule_config/cron_frequency' =>
                'url_rewrite_generator/product_entity_config/cron_frequency',
            'url_rewrite_generator/product_schedule_config/process_batch_size' =>
                'url_rewrite_generator/product_entity_config/process_batch_size'
        ];

        $tableName = $connection->getTableName('core_config_data');

        $connection->delete(
            $tableName,
            [
                'path IN (?)' => array_merge(
                    array_values($paths),
                    ['url_rewrite_generator/general/include_invisible_product']
                )
            ]
        );

        $select = $connection->select()
            ->from($tableName, ['config_id', 'path'])
            ->where('path IN (?)', array_keys($paths));

        $request = [];
        foreach ($connection->fetchPairs($select) as $entityId => $path) {
            if (isset($paths[$path])) {
                $request[] = [
                    'config_id' => $entityId,
                    'path' => $paths[$path]
                ];
            }
        }

        if ($request) {
            $connection->insertOnDuplicate(
                $tableName,
                $request,
                ['path']
            );
        }

        $connection->endSetup();
        return $this;
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
