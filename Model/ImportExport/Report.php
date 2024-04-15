<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport;

use Magento\Framework\Exception\LocalizedException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Class Report
 * used to add report to CSV file source
 */
class Report
{
    public const REPORT_FILENAME_TEMPLATE = 'operation_%d.csv';

    /**
     * @var FileSystem\Pool
     */
    private FileSystem\Pool $filePool;

    /**
     * @param FileSystem\Pool $filePool
     */
    public function __construct(FileSystem\Pool $filePool)
    {
        $this->filePool = $filePool;
    }

    /**
     * @param int $operationId
     * @param array $rows
     * @return void
     * @throws LocalizedException
     */
    public function save(int $operationId, array $rows = []): void
    {
        if (!$file = $this->filePool->get(sprintf(self::REPORT_FILENAME_TEMPLATE, $operationId))) {
            return;
        }

        $file->addRow([
            UrlRewrite::REQUEST_PATH,
            UrlRewrite::TARGET_PATH,
            UrlRewrite::REDIRECT_TYPE,
            UrlRewrite::STORE_ID,
            ImportInterface::COLUMN_MESSAGES
        ]);

        foreach ($rows as $row) {
            $file->addRow($row);
        }
    }
}
