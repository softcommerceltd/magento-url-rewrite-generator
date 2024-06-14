<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator as CatalogCategoryUrlRewriteGenerator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\MergeDataProviderFactory;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use SoftCommerce\Core\Framework\DataStorageInterface;
use SoftCommerce\Core\Framework\DataStorageInterfaceFactory;
use SoftCommerce\Core\Framework\MessageStorageInterface;
use SoftCommerce\Core\Framework\MessageStorageInterfaceFactory;
use SoftCommerce\Core\Model\Source\Status;
use function implode;

/**
 * @inheritDoc
 */
class CategoryUrlRewriteGenerator implements UrlRewriteInterface
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
     * @var CatalogCategoryUrlRewriteGenerator
     */
    private CatalogCategoryUrlRewriteGenerator $categoryUrlRewriteGenerator;

    /**
     * @var MergeDataProviderFactory
     */
    private MergeDataProviderFactory $mergeUrlDataProviderFactory;

    /**
     * @var UrlPersistInterface
     */
    private UrlPersistInterface $urlPersist;

    /**
     * @var array
     */
    private array $urlInMemory = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CatalogCategoryUrlRewriteGenerator $categoryUrlRewriteGenerator
     * @param DataStorageInterfaceFactory $dataStorageFactory
     * @param MergeDataProviderFactory $mergeDataProviderFactory
     * @param MessageStorageInterfaceFactory $messageStorageFactory
     * @param UrlPersistInterface $urlPersist
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CatalogCategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        DataStorageInterfaceFactory $dataStorageFactory,
        MergeDataProviderFactory $mergeDataProviderFactory,
        MessageStorageInterfaceFactory $messageStorageFactory,
        UrlPersistInterface $urlPersist
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->responseStorage = $dataStorageFactory->create();
        $this->mergeUrlDataProviderFactory = $mergeDataProviderFactory;
        $this->messageStorage = $messageStorageFactory->create();
        $this->urlPersist = $urlPersist;
    }

    /**
     * @return DataStorageInterface
     */
    public function getResponseStorage(): DataStorageInterface
    {
        return $this->responseStorage;
    }

    /**
     * @return MessageStorageInterface
     */
    public function getMessageStorage(): MessageStorageInterface
    {
        return $this->messageStorage;
    }

    /**
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds): void
    {
        if (empty($entityIds)) {
            return;
        }

        $this->initialize();

        foreach ($entityIds as $entityId) {
            $category = null;

            try {
                $category = $this->categoryRepository->get($entityId);
                $this->generate($category);
                $this->getResponseStorage()->addData($entityId);
                $this->getMessageStorage()->addData(
                    __(
                        'Url rewrites have been generated. [Category: %1, Store: %2]',
                        $entityId,
                        implode(', ', $category->getStoreIds() ?: '')
                    ),
                    $entityId
                );
            } catch (\Exception $e) {
                $this->getMessageStorage()->addData(
                    __(
                        'Could not generate URL rewrites. [Category: %1, Store: %2, Error: %3]',
                        $entityId,
                        $category ? implode(', ', $category->getStoreIds() ?: '') : 0,
                        $e->getMessage()
                    ),
                    $entityId,
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
        $this->responseStorage->resetData();
        $this->messageStorage->resetData();
    }

    /**
     * @param CategoryInterface $category
     * @return void
     * @throws NoSuchEntityException
     * @throws UrlAlreadyExistsException
     */
    private function generate(CategoryInterface $category): void
    {
        $mergeUrlDataProvider = $this->mergeUrlDataProviderFactory->create();

        foreach ($category->getStoreIds() ?: [] as $storeId) {
            $storeId = (int) $storeId;
            if ($storeId === Store::DEFAULT_STORE_ID) {
                continue;
            }

            /** @var Category|CategoryInterface $category */
            $category = $this->categoryRepository->get($category->getEntityId(), $storeId);
            $urlRewrites = $this->categoryUrlRewriteGenerator->generate($category, true);

            foreach (array_keys($urlRewrites) as $index) {
                if (isset($this->urlInMemory[$index])) {
                    unset($urlRewrites[$index]);
                } else {
                    $this->urlInMemory[$index] = true;
                }
            }
            $mergeUrlDataProvider->merge($urlRewrites);
        }

        if ($urlData = $mergeUrlDataProvider->getData()) {
            $this->urlPersist->replace($urlData);
        }
    }
}
