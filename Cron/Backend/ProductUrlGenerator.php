<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Cron\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use SoftCommerce\Core\Logger\LogProcessorInterface;
use SoftCommerce\Core\Model\Source\Status;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteInterface;

/**
 * Class ProductUrlGenerator used to
 * generate URL rewrites for product entity.
 */
class ProductUrlGenerator
{
    private const XML_PATH_BATCH_SIZE = 'url_rewrite_generator/product_schedule_config/process_batch_size';

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @var LogProcessorInterface
     */
    private LogProcessorInterface $logger;

    /**
     * @var UrlRewriteInterface
     */
    private UrlRewriteInterface $urlRewrite;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param LogProcessorInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param UrlRewriteInterface $urlRewrite
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LogProcessorInterface $logger,
        ResourceConnection $resourceConnection,
        UrlRewriteInterface $urlRewrite,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->connection = $resourceConnection->getConnection();
        $this->urlRewrite = $urlRewrite;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $batch = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE) ?: 20;

        foreach (array_chunk($this->getProductIds(), $batch) as $batchEntityIds) {
            try {
                $this->urlRewrite->execute($batchEntityIds);
                if ($result = $this->urlRewrite->getResponseStorage()->getData()) {
                    $this->logger->execute(
                        Status::SUCCESS,
                        [
                           sprintf('Generated URL IDs: %s', implode(',', $result))
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->execute(
                    Status::ERROR,
                    [
                        $e->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * @return array
     */
    protected function getProductIds(): array
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName('catalog_product_entity'), 'entity_id')
            ->order('entity_id ASC');

        return array_map('intval', $this->connection->fetchCol($select));
    }
}
