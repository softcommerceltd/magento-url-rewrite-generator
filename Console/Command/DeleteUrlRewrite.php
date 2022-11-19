<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class DeleteUrlRewrite extends Command
{
    private const COMMAND_NAME = 'url_rewrite:delete';
    private const ENTITY_FILTER = 'entity';
    private const STORE_FILTER = 'store';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string|null $name
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        string $name = null
    ) {
        $this->connection = $resourceConnection->getConnection();
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete URL rewrites for a given entity.')
            ->setDefinition([
                new InputOption(
                    self::ENTITY_FILTER,
                    '-e',
                    InputOption::VALUE_REQUIRED,
                    'Entity filter. [category, product]. Excepts comma seperated values.'
                ),
                new InputOption(
                    self::STORE_FILTER,
                    '-s',
                    InputOption::VALUE_REQUIRED,
                    'Store ID filter. Excepts comma seperated values.'
                )
            ]);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$entityFilter = $input->getOption(self::ENTITY_FILTER)) {
            $output->writeln(
                '<error>Specify entit(y|ies).</error> <comment>Available options: category|product</comment>'
            );
            return Cli::RETURN_FAILURE;
        }

        $entity = explode(',', strtolower($entityFilter));
        $entity = array_map('trim', $entity);

        $store = [];
        if ($storeFilter = $input->getOption(self::STORE_FILTER)) {
            $store = explode(',', str_replace(', ', '', $storeFilter));
        }

        try {
            $result = $this->process($entity, $store);
            $output->writeln(
                sprintf(
                    '<info>A total of %s URL rewrites have been deleted. Effected entities: %s.</info>',
                    $result,
                    implode(', ', $entity)
                )
            );
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param array $entity
     * @param array $store
     * @return int
     */
    protected function process(array $entity, array $store = []): int
    {
        $condition = ['entity_type IN (?)' => $entity];
        if ($store) {
            $condition['store_id IN (?)'] = $store;
        }

        return (int) $this->connection->delete(
            $this->connection->getTableName('url_rewrite'),
            $condition
        );
    }
}
