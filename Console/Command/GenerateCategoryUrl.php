<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @inheritDoc
 */
class GenerateCategoryUrl extends AbstractGenerator
{
    private const COMMAND_NAME = 'url_rewrite:generate:category';

    /**
     * @inheritDoc
     */
    protected function configure(): void
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
    protected function getAllIds(InputInterface $input, ?int $storeId = null): array
    {
        if ($idFilter = $input->getOption(self::ID_FILTER)) {
            $entityIds = explode(',', str_replace(' ', '', $idFilter));
        } else {
            $select = $this->getConnection()->select()
                ->from($this->getConnection()->getTableName('catalog_category_entity'), 'entity_id')
                ->where('parent_id > ?', 1);
            $entityIds = $this->getConnection()->fetchCol($select);
        }

        return array_map('intval', $entityIds);
    }
}
