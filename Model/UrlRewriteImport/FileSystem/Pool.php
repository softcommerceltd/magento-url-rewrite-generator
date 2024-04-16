<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem;

use Magento\Framework\Exception\LocalizedException;
use SplFileObject;

/**
 * Class Pool
 * used to create CSV file source instance
 */
class Pool
{
    /**
     * @var CsvSourceFactory
     */
    private CsvSourceFactory $csvSourceFactory;

    /**
     * @var Directory
     */
    private Directory $directory;

    /**
     * @param CsvSourceFactory $csvSourceFactory
     * @param Directory $directory
     */
    public function __construct(
        CsvSourceFactory $csvSourceFactory,
        Directory $directory
    ) {
        $this->csvSourceFactory = $csvSourceFactory;
        $this->directory = $directory;
    }

    /**
     * @param string $filename
     * @return CsvSource
     * @throws LocalizedException
     */
    public function get(string $filename): CsvSource
    {
        try {
            return $this->csvSourceFactory->create(
                [
                    'splFileObject' => new SplFileObject($this->directory->getPath() . '/' . $filename)
                ]
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Cannot open file with the name: %1', $filename),
                $e
            );
        }
    }
}
