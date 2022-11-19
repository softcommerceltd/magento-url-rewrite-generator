<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Magento\Catalog\Model\Product\Visibility;
use Symfony\Component\Console\Input\InputOption;

/**
 * @inheritDoc
 */
class GenerateProductUrl extends AbstractGenerator
{
    private const COMMAND_NAME = 'url_rewrite:product:generate';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generates URL rewrites for Product entity.')
            ->setDefinition([
                new InputOption(
                    self::ID_FILTER,
                    '-i',
                    InputOption::VALUE_REQUIRED,
                    'Product entity ID filter'
                )
            ]);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function getAllIds(): array
    {
        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                'entity_id'
            )
            ->joinLeft(
                ['ea' => $this->connection->getTableName('eav_attribute')],
                'ea.attribute_code = \'visibility\'',
                null
            )
            ->joinLeft(
                ['cpei' => $this->connection->getTableName('catalog_product_entity_int')],
                'cpe.entity_id  = cpei.entity_id' .
                ' AND ea.attribute_id = cpei.attribute_id AND cpei.store_id = 0',
                null
            )
            ->where(
                'cpei.value IN (?)',
                [
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH
                ]
            )
            ->order('entity_id ASC');

        return array_map('intval', $this->connection->fetchCol($select));
    }
}
