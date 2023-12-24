<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Console\Command;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Filter\FilterManager;
use Magento\Store\Model\ScopeInterface;
use SoftCommerce\Core\Model\Eav\GetEntityTypeIdInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class GenerateProductUrlKeyByAttribute extends Command
{
    private const COMMAND_NAME = 'url_rewrite:product_url_key:generate';
    private const ATTRIBUTE_ID_ARG = 'attribute_id';
    private const ATTRIBUTE_CODE_ARG = 'attribute_code';
    private const PRODUCT_ID_ARG = 'product_id';

    /**
     * @var AdapterInterface
     */
    protected AdapterInterface $connection;

    /**
     * @var array
     */
    private array $dataInMemory = [];

    /**
     * @var FilterManager
     */
    private FilterManager $filter;

    /**
     * @var GetEntityTypeIdInterface
     */
    private GetEntityTypeIdInterface $getEntityTypeId;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param FilterManager $filter
     * @param GetEntityTypeIdInterface $getEntityTypeId
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $name
     */
    public function __construct(
        FilterManager $filter,
        GetEntityTypeIdInterface $getEntityTypeId,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        string $name = null
    ) {
        $this->filter = $filter;
        $this->getEntityTypeId = $getEntityTypeId;
        $this->connection = $resourceConnection->getConnection();
        $this->scopeConfig = $scopeConfig;

        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generates product url_key attribute value.')
            ->setDefinition([
                new InputOption(
                    self::ATTRIBUTE_ID_ARG,
                    '-a',
                    InputOption::VALUE_REQUIRED,
                    'Attribute entity ID argument'
                ),
                new InputOption(
                    self::ATTRIBUTE_CODE_ARG,
                    '-c',
                    InputOption::VALUE_REQUIRED,
                    'Attribute code argument'
                ),
                new InputOption(
                    self::PRODUCT_ID_ARG,
                    '-i',
                    InputOption::VALUE_REQUIRED,
                    'Product entity ID argument'
                )
            ]);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $attributeId = $input->getOption(self::ATTRIBUTE_ID_ARG);
        $attributeCode = $input->getOption(self::ATTRIBUTE_CODE_ARG);

        if (!$attributeId || !$attributeCode) {
            $output->writeln("<error>Please provide attribute ID or code.</error>");
            return Cli::RETURN_FAILURE;
        }

        if (!$attributeId && $attributeCode = $input->getOption(self::ATTRIBUTE_CODE_ARG)) {
            $attributeId = $this->getAttributeIdByCode($attributeCode);
        }

        if (!$attributeId) {
            $output->writeln('<error>Could not retrieve attribute.</error>');
            return Cli::RETURN_FAILURE;
        }

        if ($productIdArg = $input->getOption(self::PRODUCT_ID_ARG)) {
            $productIds = explode(',', str_replace(' ', '', $productIdArg));
        } else {
            $productIds = $this->getAllIds();
        }

        $attributeData = $this->getAttributeData((int) $attributeId);

        foreach ($productIds as $productId) {
            try {
                $result = $this->process($productId, $attributeData);
                if ($result) {
                    $output->writeln(
                        sprintf(
                            '<info>A URL key for the product with <comment>ID: %s</comment> has been generated.</info>',
                            $productId
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf(
                            '<comment>Nothing to generate for the product with <info>ID: %s</info>.</comment>',
                            $productId
                        )
                    );
                }
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param int $productId
     * @param array $attributeData
     * @return int
     */
    private function process(int $productId, array $attributeData): int
    {
        $attributeId = key($attributeData);
        $attributeTypeId = current($attributeData);

        $urlKeyAttributeData = $this->getAttributeData('url_key');
        $urlKeyAttributeId = key($urlKeyAttributeData);
        $urlKeyAttributeTypeId = current($urlKeyAttributeData);

        if (!$urlKeyAttributeId
            || !$attributeValue = $this->getAttributeValue($productId, $attributeId, $attributeTypeId)
        ) {
            return 0;
        }

        $existingAttributeValue = $this->getAttributeValue($productId, $urlKeyAttributeId, $urlKeyAttributeTypeId);

        $urlKey = $this->filter->translitUrl($attributeValue);

        if ($urlKey === $existingAttributeValue) {
            return 1;
        }

        $request = [
            'attribute_id' => $urlKeyAttributeId,
            'store_id' => 0,
            'entity_id' => $productId,
            'value' => $urlKey
        ];

        return (int) $this->connection->insertOnDuplicate(
            $this->connection->getTableName("catalog_product_entity_$urlKeyAttributeTypeId"),
            $request,
            ['value']
        );
    }

    /**
     * @param int|string $attributeCodeOrId
     * @return array
     */
    private function getAttributeData(int|string $attributeCodeOrId): array
    {
        if (!isset($this->dataInMemory[$attributeCodeOrId])) {
            if (is_numeric($attributeCodeOrId)) {
                $entityType = 'id';
            } else {
                $entityType = 'code';
            }

            $select = $this->connection->select()
                ->from($this->connection->getTableName('eav_attribute'), ['attribute_id', 'backend_type'])
                ->where("attribute_{$entityType} = ?", $attributeCodeOrId)
                ->where('entity_type_id = ?', $this->getEntityTypeId->execute());

            $this->dataInMemory[$attributeCodeOrId] = $this->connection->fetchPairs($select);
        }

        return $this->dataInMemory[$attributeCodeOrId];
    }

    /**
     * @param int $productId
     * @param int $attributeId
     * @param string $attributeTypeId
     * @return string|null
     */
    private function getAttributeValue(int $productId, int $attributeId, string $attributeTypeId): ?string
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName("catalog_product_entity_$attributeTypeId"), 'value')
            ->where('attribute_id = ?', $attributeId)
            ->where('entity_id = ?', $productId)
            ->where('store_id = ?', 0);

        return $this->connection->fetchOne($select);
    }

    /**
     * @inheritDoc
     */
    private function getAllIds(): array
    {
        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                'cpe.entity_id'
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
            ->order('entity_id DESC');

        if (!$this->scopeConfig->isSetFlag(
            'url_rewrite_generator/product_entity_config/include_invisible_product',
            ScopeInterface::SCOPE_WEBSITE
        )) {
            $select->where(
                'cpei.value IN (?)',
                [
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH
                ]
            );
        }

        return array_map('intval', $this->connection->fetchCol($select));
    }
}
