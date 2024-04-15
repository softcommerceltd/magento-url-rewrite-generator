<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface ImportInterface
 * used to import URL Rewrites
 */
interface ImportInterface
{
    /**
     * CSV PROCESS ROW LIMIT
     */
    public const PROCESS_ROW_BATCH = 1000;
    public const PROCESS_PAYLOAD_BATCH = 100;

    /**
     * GLOBAL ATTRIBUTES
     */
    public const FILENAME = 'filename';
    public const OFFSET = 'offset';
    public const SIZE = 'size';

    /**
     * CSV ROW NO FIELDS
     */
    public const COLUMN_NO_REQUEST_PATH = 0;
    public const COLUMN_NO_TARGET_PATH = 1;
    public const COLUMN_NO_REDIRECT_TYPE = 2;
    public const COLUMN_NO_STORE_CODE = 3;
    public const COLUMN_NO_DESCRIPTION = 4;
    public const COLUMN_NO_METADATA = 5;
    public const COLUMN_NO_MESSAGES = 6;
    public const COLUMN_MESSAGES = 'messages';

    /**
     * @param int $operationId
     * @param string $filename
     * @param int $offset
     * @param int $size
     * @return void
     * @throws LocalizedException
     */
    public function execute(int $operationId, string $filename, int $offset, int $size): void;
}
