<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Adapter\AdapterInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
abstract class AbstractGenerator extends Command
{
    protected const ID_FILTER = 'id';
    protected const STORE_ID_ARG = 'store_id';
    private const ARRAY_CHUNK_SIZE = 20;

    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $connection;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var UrlRewriteInterface
     */
    protected UrlRewriteInterface $urlRewrite;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlRewriteInterface $urlRewrite
     * @param string|null $name
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        UrlRewriteInterface $urlRewrite,
        string $name = null
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->scopeConfig = $scopeConfig;
        $this->urlRewrite = $urlRewrite;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = $input->getOption(self::STORE_ID_ARG);
        if (null !== $storeId) {
            $storeId = (int) $storeId;
        }

        foreach (array_chunk($this->getAllIds($input, $storeId), self::ARRAY_CHUNK_SIZE) as $payload) {
            try {
                $this->urlRewrite->execute($payload, $storeId);
                $result = $this->urlRewrite->getResponseStorage()->getData();

                if ($result) {
                    $output->writeln(
                        sprintf(
                            '<info>URLs have been generated <comment>[IDs: %s, Store: %s]</comment></info>',
                            implode(',', $result),
                            $storeId
                        )
                    );
                } else {
                    $output->writeln('<comment>Nothing to generate</comment>');
                }
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param int|null $storeId
     * @return array
     */
    abstract protected function getAllIds(InputInterface $input, ?int $storeId = null): array;
}
