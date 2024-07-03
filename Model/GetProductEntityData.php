<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\ScopeInterface;
use SoftCommerce\Core\Model\Store\WebsiteStorageInterface;
use SoftCommerce\Core\Model\Utils\GetEntityMetadataInterface;

/**
 * @inheritDoc
 */
class GetProductEntityData implements GetProductEntityDataInterface
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var GetEntityMetadataInterface
     */
    private GetEntityMetadataInterface $getEntityMetadata;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var WebsiteStorageInterface
     */
    private WebsiteStorageInterface $websiteStorage;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetEntityMetadataInterface $getEntityMetadata
     * @param ScopeConfigInterface $scopeConfig
     * @param WebsiteStorageInterface $websiteStorage
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetEntityMetadataInterface $getEntityMetadata,
        ScopeConfigInterface $scopeConfig,
        WebsiteStorageInterface $websiteStorage
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->getEntityMetadata = $getEntityMetadata;
        $this->scopeConfig = $scopeConfig;
        $this->websiteStorage = $websiteStorage;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $productIds = [], ?int $storeId = null): array
    {
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
            );

        if ($productIds) {
            $select->where("cpe.$linkField IN (?)", $productIds);
        } elseif (!$this->scopeConfig->isSetFlag(
            'url_rewrite_generator/product_entity_config/include_invisible_product',
            ScopeInterface::SCOPE_WEBSITE
        )) {
            $select->joinLeft(
                ['cpei' => $this->connection->getTableName('catalog_product_entity_int')],
                'cpe.entity_id  = cpei.entity_id' .
                ' AND ea.attribute_id = cpei.attribute_id AND cpei.store_id = 0',
                null
            )
            ->where(
                'cpei.value IN (?)',
                [
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH
                ]
            )->order('entity_id DESC');
        }

        if (null !== $storeId) {
            if ($storeId === 0) {
                $websiteId = $this->websiteStorage->getDefaultWebsiteId();
            } else {
                $websiteId = $this->websiteStorage->getStoreIdToWebsiteId($storeId);
            }
            if ($websiteId) {
                $select->where('cpw.website_id = ?', $websiteId);
            }
        }

        $websiteIdToStoreIds = $this->websiteStorage->getWebsiteIdToStoreIds();
        return array_map(function ($item) use ($websiteIdToStoreIds, $storeId) {
            if (null !== $storeId) {
                $storeIds = [$storeId];
            } else {
                $storeIds = [];
                foreach (explode(',', $item['website_id'] ?? '') as $websiteId) {
                    if (isset($websiteIdToStoreIds[$websiteId])) {
                        $storeIds += $websiteIdToStoreIds[$websiteId];
                    }
                }
            }

            $item['store_id'] = $storeIds;
            $item['category_id'] = isset($item['category_id']) ? explode(',', $item['category_id']) : [];
            return $item;
        }, $this->connection->fetchAll($select));
    }
}
