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
        foreach (array_chunk($this->getAllIds($input), self::ARRAY_CHUNK_SIZE) as $payload) {
            try {
                $this->urlRewrite->execute($payload);
                if ($result = $this->urlRewrite->getResponseStorage()->getData()) {
                    $output->writeln(
                        sprintf(
                            '<info>URL rewrites have been generated. </info><comment>Effected IDs: %s</comment>',
                            implode(',', $result)
                        )
                    );
                } else {
                    $output->writeln('<comment>No URL rewrites have been generated.</comment>');
                }
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    abstract protected function getAllIds(InputInterface $input): array;
}
