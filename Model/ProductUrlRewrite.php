<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\MergeDataProviderFactory;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use SoftCommerce\Core\Framework\DataStorageInterface;
use SoftCommerce\Core\Framework\DataStorageInterfaceFactory;
use SoftCommerce\Core\Framework\MessageStorageInterface;
use SoftCommerce\Core\Framework\MessageStorageInterfaceFactory;
use SoftCommerce\Core\Model\Source\Status;
use SoftCommerce\Core\Model\Utils\GetEntityMetadataInterface;
use SoftCommerce\Core\Model\Utils\WebsiteStorageInterface;
use function array_map;
use function explode;
use function implode;

/**
 * @inheritDoc
 */
class ProductUrlRewrite implements UrlRewriteInterface
{
    /**
     * @var DataStorageInterface
     */
    private DataStorageInterface $responseStorage;

    /**
     * @var MessageStorageInterface
     */
    private MessageStorageInterface $messageStorage;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var GetEntityMetadataInterface
     */
    private GetEntityMetadataInterface $getEntityMetadata;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * @var ProductResource
     */
    private ProductResource $productResource;

    /**
     * @var MergeDataProviderFactory
     */
    private MergeDataProviderFactory $mergeUrlDataProviderFactory;

    /**
     * @var ProductUrlPathGenerator
     */
    private ProductUrlPathGenerator $productUrlPathGenerator;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var UrlFinderInterface
     */
    private UrlFinderInterface $urlFinder;

    /**
     * @var UrlPersistInterface
     */
    private UrlPersistInterface $urlPersist;

    /**
     * @var UrlRewriteFactory
     */
    private UrlRewriteFactory $urlRewriteFactory;

    /**
     * @var WebsiteStorageInterface
     */
    private WebsiteStorageInterface $websiteStorage;

    /**
     * @var array
     */
    private array $request = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param DataStorageInterfaceFactory $dataStorageFactory
     * @param GetEntityMetadataInterface $getEntityMetadata
     * @param MergeDataProviderFactory $mergeDataProviderFactory
     * @param MessageStorageInterfaceFactory $messageStorageFactory
     * @param ResourceConnection $resourceConnection
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlFinderInterface $urlFinder
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param UrlPersistInterface $urlPersist
     * @param WebsiteStorageInterface $websiteStorage
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        DataStorageInterfaceFactory $dataStorageFactory,
        GetEntityMetadataInterface $getEntityMetadata,
        MergeDataProviderFactory $mergeDataProviderFactory,
        MessageStorageInterfaceFactory $messageStorageFactory,
        ResourceConnection $resourceConnection,
        ProductFactory $productFactory,
        ProductResource $productResource,
        ProductUrlPathGenerator $productUrlPathGenerator,
        ScopeConfigInterface $scopeConfig,
        UrlFinderInterface $urlFinder,
        UrlRewriteFactory $urlRewriteFactory,
        UrlPersistInterface $urlPersist,
        WebsiteStorageInterface $websiteStorage
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->responseStorage = $dataStorageFactory->create();
        $this->getEntityMetadata = $getEntityMetadata;
        $this->mergeUrlDataProviderFactory = $mergeDataProviderFactory;
        $this->connection = $resourceConnection->getConnection();
        $this->messageStorage = $messageStorageFactory->create();
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->scopeConfig = $scopeConfig;
        $this->urlFinder = $urlFinder;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlPersist = $urlPersist;
        $this->websiteStorage = $websiteStorage;
    }

    /**
     * @inheritDoc
     */
    public function getResponseStorage(): DataStorageInterface
    {
        return $this->responseStorage;
    }

    /**
     * @inheritDoc
     */
    public function getMessageStorage(): MessageStorageInterface
    {
        return $this->messageStorage;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function execute(array $entityIds): void
    {
        if (empty($entityIds)) {
            return;
        }

        $this->initialize();

        foreach ($this->getData($entityIds) as $item) {
            $productId = (int) ($item[$this->getEntityMetadata->getLinkField()] ?? null);
            if (!$productId || !$storeIds = $item['store_id'] ?? []) {
                continue;
            }

            $categoryIds = $item['category_id'] ?? [];

            try {
                $this->generate($productId, $storeIds, $categoryIds);
                $this->getResponseStorage()->addData($productId);
                $this->getMessageStorage()->addData(
                    __(
                        'Url rewrites have been generated. [Product: %1, Store: %2]',
                        $productId,
                        implode(', ', $storeIds)
                    ),
                    $productId
                );
            } catch (\Exception $e) {
                $this->getMessageStorage()->addData(
                    __(
                        'Could not generate URL rewrites. [Product: %1, Store: %2, Error: %3]',
                        $productId,
                        implode(', ', $storeIds),
                        $e->getMessage()
                    ),
                    $productId,
                    Status::ERROR
                );
            }
        }
    }

    /**
     * @return void
     */
    private function initialize(): void
    {
        $this->request = [];
        $this->responseStorage->resetData();
        $this->messageStorage->resetData();
    }

    /**
     * @param int $productId
     * @param array $storeIds
     * @param array $categoryIds
     * @return void
     * @throws NoSuchEntityException
     * @throws UrlAlreadyExistsException
     */
    private function generate(int $productId, array $storeIds, array $categoryIds = []): void
    {
        $attributeCodes = [ProductInterface::NAME, ProductInterface::VISIBILITY, 'url_key'];
        $identifierFieldName = $this->getEntityMetadata->getIdentifierField();
        foreach ($storeIds as $storeId) {
            $attributeValues = $this->productResource->getAttributeRawValue($productId, $attributeCodes, $storeId);
            if (!is_array($attributeValues)) {
                throw new LocalizedException(
                    __('Could not retrieve required attributes.')
                );
            }

            $attributeValues[$identifierFieldName] = $productId;
            $attributeValues['store_id'] = $storeId;
            $attributeValues['save_rewrites_history'] = true;
            $product = $this->productFactory->create();
            $product->setData($attributeValues);
            $this->request[$storeId] = $product;
        }

        $mergeUrlDataProvider = $this->mergeUrlDataProviderFactory->create();
        $mergeUrlDataProvider->merge($this->generateCanonicalUrlRewrites());

        if ($categoryIds && $this->canGenerateCategoryRewrites()) {
            $mergeUrlDataProvider->merge($this->generateCategoryUrlRewrites($categoryIds));
        }

        $mergeUrlDataProvider->merge($this->generateExistingUrlRewrites($productId, $storeIds));

        if ($urlData = $mergeUrlDataProvider->getData()) {
            $this->urlPersist->replace($urlData);
        }
    }

    /**
     * @return UrlRewrite[]
     */
    private function generateCanonicalUrlRewrites(): array
    {
        $result = [];
        /** @var Product $product */
        foreach ($this->request as $product) {
            if (!$this->productUrlPathGenerator->getUrlPath($product)) {
                continue;
            }

            $result[$product->getStoreId()] = $this->urlRewriteFactory->create()
                ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
                ->setEntityId($product->getEntityId())
                ->setRequestPath($this->productUrlPathGenerator->getUrlPathWithSuffix($product, $product->getStoreId()))
                ->setTargetPath($this->productUrlPathGenerator->getCanonicalUrlPath($product))
                ->setStoreId($product->getStoreId());
        }

        return $result;
    }

    /**
     * @param array $categoryIds
     * @return UrlRewrite[]
     * @throws NoSuchEntityException
     */
    private function generateCategoryUrlRewrites(array $categoryIds): array
    {
        $result = [];
        /** @var Product $product */
        foreach ($this->request as $product) {
            foreach ($categoryIds as $categoryId) {
                $categoryId = (int) $categoryId;
                $storeId = (int) $product->getStoreId();

                if (!$urlRewrite = $this->generateCategoryUrlRewrite($categoryId, $storeId, $product)) {
                    continue;
                }

                $result[] = $urlRewrite;
                $category = $this->categoryRepository->get($categoryId, $storeId);

                foreach ($category->getAnchorsAbove() as $categoryParentId) {
                    if ($urlRewrite = $this->generateCategoryUrlRewrite((int) $categoryParentId, $storeId, $product)) {
                        $result[] = $urlRewrite;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     * @param Product $product
     * @return UrlRewrite|null
     * @throws NoSuchEntityException
     */
    private function generateCategoryUrlRewrite(int $categoryId, int $storeId, Product $product): ?UrlRewrite
    {
        $category = $this->categoryRepository->get($categoryId, $storeId);
        if ($category->getParentId() == Category::TREE_ROOT_ID) {
            return null;
        }

        $requestPath = $this->productUrlPathGenerator->getUrlPathWithSuffix($product, $storeId, $category);
        $targetPath = $this->productUrlPathGenerator->getCanonicalUrlPath($product, $category);
        return $this->urlRewriteFactory->create()
            ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
            ->setEntityId($product->getId())
            ->setRequestPath($requestPath)
            ->setTargetPath($targetPath)
            ->setStoreId($storeId)
            ->setMetadata(['category_id' => $category->getEntityId()]);
    }

    /**
     * @param int $productId
     * @param array $storeIds
     * @return array
     * @throws NoSuchEntityException
     */
    private function generateExistingUrlRewrites(int $productId, array $storeIds): array
    {
        $currentUrlRewrites = $this->urlFinder->findAllByData(
            [
                UrlRewrite::STORE_ID => $storeIds,
                UrlRewrite::ENTITY_ID => $productId,
                UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
            ]
        );

        $result = [];
        foreach ($currentUrlRewrites as $currentUrlRewrite) {
            $metadata = $currentUrlRewrite->getMetadata();
            $category = null;
            if (isset($metadata['category_id'])) {
                $category = $this->categoryRepository->get($metadata['category_id'], $currentUrlRewrite->getStoreId());
            }

            if ($urlRewrite = $this->generateExistingUrlRewrite($currentUrlRewrite, $category)) {
                $result[] = $urlRewrite;
            }
        }

        return $result;
    }

    /**
     * @param UrlRewrite $urlRewrite
     * @param Category|null $category
     * @return UrlRewrite|null
     */
    private function generateExistingUrlRewrite(UrlRewrite $urlRewrite, ?Category $category = null): ?UrlRewrite
    {
        $storeId = $urlRewrite->getStoreId();
        $productId = $urlRewrite->getEntityId();
        $product = $this->request[$storeId] ?? null;
        if (!$product || $productId != $product->getEntityId()) {
            return null;
        }

        if ($urlRewrite->getIsAutogenerated()) {
            $targetPath = $this->productUrlPathGenerator->getUrlPathWithSuffix($product, $storeId, $category);
            $redirectType = OptionProvider::PERMANENT;
        } else {
            $targetPath = $urlRewrite->getRedirectType()
                ? $this->productUrlPathGenerator->getUrlPathWithSuffix($product, $storeId, $category)
                : $urlRewrite->getTargetPath();
            $redirectType = $urlRewrite->getRedirectType();
        }

        if ($urlRewrite->getRequestPath() === $targetPath) {
            return null;
        }

        return $this->urlRewriteFactory->create()
            ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
            ->setEntityId($productId)
            ->setRequestPath($urlRewrite->getRequestPath())
            ->setTargetPath($targetPath)
            ->setRedirectType($redirectType)
            ->setStoreId($storeId)
            ->setDescription($urlRewrite->getDescription())
            ->setIsAutogenerated(0)
            ->setMetadata($urlRewrite->getMetadata());
    }

    /**
     * @param array $productIds
     * @return array
     * @throws \Exception
     */
    private function getData(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $linkField = $this->getEntityMetadata->getLinkField();
        $columns = ['cpe.entity_id', 'cpe.' . ProductInterface::SKU];
        if ($linkField !== $this->getEntityMetadata->getIdentifierField()) {
            $columns[] = "cpe.$linkField";
        }

        $select = $this->connection->select()
            ->from(
                ['cpe' => $this->connection->getTableName('catalog_product_entity')],
                $columns
            )
            ->joinLeft(
                ['ea' => $this->connection->getTableName('eav_attribute')],
                'ea.attribute_code = \'visibility\'',
                null
            )
            ->joinLeft(
                ['ccp' => $this->connection->getTableName('catalog_category_product')],
                'cpe.entity_id = ccp.product_id',
                [
                    'category_id' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT ccp.category_id)')
                ]
            )
            ->joinLeft(
                ['cpw' => $this->connection->getTableName('catalog_product_website')],
                'cpe.entity_id = cpw.product_id',
                [
                    'website_id' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT cpw.website_id)')
                ]
            )->group(
                'cpe.entity_id'
            )
            ->where("cpe.$linkField IN (?)", $productIds);

        $websiteIdToStoreIds = $this->websiteStorage->getWebsiteIdToStoreIds();
        return array_map(function ($item) use ($websiteIdToStoreIds) {
            $storeIds = [];
            foreach (explode(',', $item['website_id'] ?? '') as $websiteId) {
                if (isset($websiteIdToStoreIds[$websiteId])) {
                    $storeIds += $websiteIdToStoreIds[$websiteId];
                }
            }
            $item['store_id'] = $storeIds;
            $item['category_id'] = isset($item['category_id']) ? explode(',', $item['category_id']) : [];
            return $item;
        }, $this->connection->fetchAll($select));
    }

    /**
     * @return bool
     */
    private function canGenerateCategoryRewrites(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag('catalog/seo/generate_category_product_rewrites');
    }
}
