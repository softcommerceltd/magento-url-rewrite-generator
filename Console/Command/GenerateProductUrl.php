<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

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
            ->from($this->connection->getTableName('catalog_product_entity'), 'entity_id')
            ->order('entity_id ASC');

        return array_map('intval', $this->connection->fetchCol($select));
    }
}
