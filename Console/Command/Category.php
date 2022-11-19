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
class Category extends AbstractGenerator
{
    private const COMMAND_NAME = 'url_rewrites:category:generate';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generates URL rewrites for Category entity.')
            ->setDefinition([
                new InputOption(
                    self::ID_FILTER,
                    '-i',
                    InputOption::VALUE_REQUIRED,
                    'Category entity ID filter'
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
            ->from($this->connection->getTableName('catalog_category_entity'), 'entity_id')
            ->where('parent_id > ?', 1);
        return array_map('intval', $this->connection->fetchCol($select));
    }
}
