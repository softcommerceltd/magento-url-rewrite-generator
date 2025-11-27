<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Cron\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use SoftCommerce\Core\Logger\LogProcessorInterface;
use SoftCommerce\Core\Model\Source\StatusInterface;
use SoftCommerce\Core\Model\Trait\ConnectionTrait;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteInterface;

/**
 * Class ProductUrlGenerator used to
 * generate URL rewrites for product entity.
 */
class ProductUrlGenerator
{
    use ConnectionTrait;

    private const XML_PATH_BATCH_SIZE = 'url_rewrite_generator/product_schedule_config/process_batch_size';
    private const XML_PATH_IS_ACTIVE = 'url_rewrite_generator/product_entity_config/enable_schedule';

    /**
     * @param LogProcessorInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param UrlRewriteInterface $urlRewrite
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private LogProcessorInterface $logger,
        private ResourceConnection $resourceConnection,
        private UrlRewriteInterface $urlRewrite,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_IS_ACTIVE)) {
            return;
        }

        $batch = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE) ?: 20;

        foreach (array_chunk($this->getProductIds(), $batch) as $batchEntityIds) {
            try {
                $this->urlRewrite->execute($batchEntityIds);
                if ($result = $this->urlRewrite->getResponseStorage()->getData()) {
                    $this->logger->execute(
                        StatusInterface::SUCCESS,
                        [
                           sprintf('Generated URL IDs: %s', implode(',', $result))
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->execute(
                    StatusInterface::ERROR,
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
        $select = $this->getConnection()->select()
            ->from($this->getConnection()->getTableName('catalog_product_entity'), 'entity_id')
            ->order('entity_id ASC');

        return array_map('intval', $this->getConnection()->fetchCol($select));
    }
}
