<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface UrlRewriteImportInterface
 * used to import URL Rewrites
 */
interface UrlRewriteImportInterface
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
    public const OPERATION_KEY = 'operation_key';
    public const ROWS_OFFSET = 'rows_offset';
    public const ENTITY_ID = 'entity_id';
    public const ENTITY_LINK = 'entity_link';
    public const META_INFORMATION = 'meta_information';

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
     * Error Messages
     */
    public const ERROR_MSG_DELIMITER = '; ';
    public const ERROR_MSG_MISSING_COLUMN_VALUE = 'Missing value for column: "%1"';

    /**
     * @param int $operationId
     * @param string $filename
     * @param int $rowsOffset
     * @return void
     * @throws LocalizedException
     */
    public function execute(int $operationId, string $filename, int $rowsOffset): void;

    /**
     * @return array
     */
    public function getErrors(): array;
}
