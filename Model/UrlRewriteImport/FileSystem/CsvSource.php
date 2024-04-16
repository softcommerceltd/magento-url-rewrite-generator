<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem;

use SplFileObject;

/**
 * Class CsvSource
 * used to provide CSV file interface
 */
class CsvSource
{
    /**
     * @var SplFileObject
     */
    private SplFileObject $splFileObject;

    /**
     * @param SplFileObject $splFileObject
     */
    public function __construct(SplFileObject $splFileObject)
    {
        $this->splFileObject = $splFileObject;
        $this->splFileObject->setFlags(SplFileObject::READ_CSV);
    }

    /**
     * @return int
     */
    public function getRowsCount(): int
    {
        $this->splFileObject->rewind();
        $this->splFileObject->seek(PHP_INT_MAX);

        return $this->splFileObject->key() + 1;
    }

    /**
     * @param int $rowsOffset
     * @param int $length
     * @return array
     */
    public function getRows(int $rowsOffset, int $length = 100): array
    {
        $rows = [];
        $i = 1;
        $this->splFileObject->seek($rowsOffset);

        do {
            $rows[$this->splFileObject->key()] = $this->splFileObject->current();
            $this->splFileObject->next();
            $i++;
        } while (!$this->splFileObject->eof() && $i <= $length);

        return $rows;
    }

    /**
     * @param array $fields
     * @return false|int
     */
    public function addRow(array $fields = []): bool|int
    {
        return $this->splFileObject->fputcsv($fields);
    }
}
