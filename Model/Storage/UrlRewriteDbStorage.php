<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\Storage;

use Magento\UrlRewrite\Model\Storage\DbStorage;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * @inheritDoc
 */
class UrlRewriteDbStorage extends DbStorage
{
    /**
     * @inheritdoc
     * @throws \Exception
     */
    protected function doReplace(array $urls): array
    {
        $data = [];
        foreach ($urls as $url) {
            $data[] = $url->toArray();
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->insertOnDuplicate(
                $this->connection->getTableName(self::TABLE_NAME),
                $data,
                [UrlRewrite::REQUEST_PATH, UrlRewrite::TARGET_PATH, UrlRewrite::REDIRECT_TYPE, UrlRewrite::STORE_ID]
            );
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

        return $urls;
    }
}
