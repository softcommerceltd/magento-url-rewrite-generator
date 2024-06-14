<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use SoftCommerce\UrlRewriteGenerator\Model\GetProductEntityDataInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @inheritDoc
 */
class GenerateProductUrl extends AbstractGenerator
{
    private const COMMAND_NAME = 'url:generate:product';

    /**
     * @var GetProductEntityDataInterface
     */
    private GetProductEntityDataInterface $getProductEntityData;

    /**
     * @param GetProductEntityDataInterface $getProductEntityData
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlRewriteInterface $urlRewrite
     * @param string|null $name
     */
    public function __construct(
        GetProductEntityDataInterface $getProductEntityData,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        UrlRewriteInterface $urlRewrite,
        string $name = null
    ) {
        $this->getProductEntityData = $getProductEntityData;
        parent::__construct($resourceConnection, $scopeConfig, $urlRewrite, $name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
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
     * @param InputInterface $input
     * @return array
     * @throws \Exception
     */
    protected function getAllIds(InputInterface $input): array
    {
        if ($idFilter = $input->getOption(self::ID_FILTER)) {
            $entityIds = explode(',', str_replace(' ', '', $idFilter));
        } else {
            $entityIds = [];
        }

        return $this->getProductEntityData->execute($entityIds);
    }
}
