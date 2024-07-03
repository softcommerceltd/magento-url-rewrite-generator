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
use Magento\Framework\Filter\FilterManager;
use SoftCommerce\Core\Model\Eav\GetEntityTypeIdInterface;
use SoftCommerce\Core\Model\Utils\GetEntityMetadataInterface;
use SoftCommerce\UrlRewriteGenerator\Model\GetProductEntityDataInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class GenerateProductUrlKey extends Command
{
    private const COMMAND_NAME = 'url:product_url_key:generate';
    private const ATTRIBUTE_CODE_ARG = 'attribute_code';
    private const PRODUCT_ID_ARG = 'product_id';
    private const STORE_ID_ARG = 'store_id';

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
     * @var GetEntityMetadataInterface
     */
    private GetEntityMetadataInterface $getEntityMetadata;

    /**
     * @var GetEntityTypeIdInterface
     */
    private GetEntityTypeIdInterface $getEntityTypeId;

    /**
     * @var GetProductEntityDataInterface
     */
    private GetProductEntityDataInterface $getProductEntityData;

    /**
     * @param FilterManager $filter
     * @param GetEntityMetadataInterface $getEntityMetadata
     * @param GetEntityTypeIdInterface $getEntityTypeId
     * @param GetProductEntityDataInterface $getProductEntityData
     * @param ResourceConnection $resourceConnection
     * @param string|null $name
     */
    public function __construct(
        FilterManager $filter,
        GetEntityMetadataInterface $getEntityMetadata,
        GetEntityTypeIdInterface $getEntityTypeId,
        GetProductEntityDataInterface $getProductEntityData,
        ResourceConnection $resourceConnection,
        string $name = null
    ) {
        $this->filter = $filter;
        $this->getEntityMetadata = $getEntityMetadata;
        $this->getEntityTypeId = $getEntityTypeId;
        $this->getProductEntityData = $getProductEntityData;
        $this->connection = $resourceConnection->getConnection();
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generates product url_key attribute value.')
            ->setDefinition([
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
                ),
                new InputOption(
                    self::STORE_ID_ARG,
                    '-s',
                    InputOption::VALUE_REQUIRED,
                    'Store ID argument'
                )
            ]);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $attributeCode = $input->getOption(self::ATTRIBUTE_CODE_ARG);
        if (!$attributeCode) {
            $attributeCode = 'name';
        }

        if ($productIdArg = $input->getOption(self::PRODUCT_ID_ARG)) {
            $productIds = explode(',', str_replace(' ', '', $productIdArg));
        } else {
            $productIds = [];
        }

        $storeId = $input->getOption(self::STORE_ID_ARG);
        if (null !== $storeId) {
            $storeId = (int) $storeId;
        }

        $attributeData = $this->getAttributeData($attributeCode);

        foreach ($this->getProductEntityData->execute($productIds) as $item) {
            $productId = (int) ($item[$this->getEntityMetadata->getLinkField()] ?? null);
            if (!$productId || !$storeIds = $item['store_id'] ?? []) {
                continue;
            }

            if (null !== $storeId) {
                $storeIds = [$storeId];
            }

            foreach ($storeIds as $storeId) {
                try {
                    $result = $this->process((int) $productId, $storeId, $attributeData);
                    if ($result) {
                        $output->writeln(
                            sprintf(
                                '<info>URL has been generated <comment>[Product: %s, Store: %s]</comment></info>',
                                $productId,
                                $storeId
                            )
                        );
                    } else {
                        $output->writeln(
                            sprintf(
                                '<comment>Nothing to generate <info>[Product: %s, Store: %s]</info></comment>',
                                $productId,
                                $storeId
                            )
                        );
                    }
                } catch (\Exception $e) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param array $attributeData
     * @return int
     */
    private function process(int $productId, int $storeId, array $attributeData): int
    {
        $attributeId = array_key_first($attributeData);
        $attributeTypeId = current($attributeData);
        $urlKeyAttribute = $this->getAttributeData('url_key');

        if (!$urlKeyAttribute
            || !$attributeValue = $this->getAttributeValue($productId, $attributeId, $storeId, $attributeTypeId)
        ) {
            return 0;
        }

        $urlKeyAttributeId = array_key_first($urlKeyAttribute);
        $urlKeyAttributeTypeId = current($urlKeyAttribute);

        $existingUrlKey = $this->getAttributeValue($productId, $urlKeyAttributeId, $storeId, $urlKeyAttributeTypeId);
        $requestUrlKey = $this->filter->translitUrl($attributeValue);

        if ($requestUrlKey === $existingUrlKey) {
            return 1;
        }

        $request = [
            'attribute_id' => $urlKeyAttributeId,
            'store_id' => $storeId,
            'entity_id' => $productId,
            'value' => $requestUrlKey
        ];

        return (int) $this->connection->insertOnDuplicate(
            $this->connection->getTableName("catalog_product_entity_$urlKeyAttributeTypeId"),
            $request,
            ['value']
        );
    }

    /**
     * @param string $attributeCodeOrId
     * @return array
     */
    private function getAttributeData(string $attributeCodeOrId): array
    {
        if (!isset($this->dataInMemory[$attributeCodeOrId])) {
            $select = $this->connection->select()
                ->from($this->connection->getTableName('eav_attribute'), ['attribute_id', 'backend_type'])
                ->where("attribute_code = ?", $attributeCodeOrId)
                ->where('entity_type_id = ?', $this->getEntityTypeId->execute());

            $this->dataInMemory[$attributeCodeOrId] = $this->connection->fetchPairs($select);
        }

        return $this->dataInMemory[$attributeCodeOrId];
    }

    /**
     * @param int $productId
     * @param int $attributeId
     * @param int $storeId
     * @param string $attributeTypeId
     * @return string|null
     */
    private function getAttributeValue(int $productId, int $attributeId, int $storeId, string $attributeTypeId): ?string
    {
        $linkField = $this->getEntityMetadata->getLinkField();

        $select = $this->connection->select()
            ->from($this->connection->getTableName("catalog_product_entity_$attributeTypeId"), 'value')
            ->where('attribute_id = ?', $attributeId)
            ->where("$linkField = ?", $productId)
            ->where('store_id = ?', $storeId);

        return $this->connection->fetchOne($select) ?: null;
    }
}
