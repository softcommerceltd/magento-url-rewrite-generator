<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Directory
 * used to provide directory interface
 */
class Directory
{
    public const URL_REWRITE_IMPORT_DIR = 'url-rewrite';

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->filesystem
            ->getDirectoryRead(DirectoryList::VAR_DIR)
            ->getAbsolutePath(static::URL_REWRITE_IMPORT_DIR);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getFilePathByName(string $fileName): string
    {
        return $this->getPath() . '/' . $fileName;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public function isFileExist(string $fileName): bool
    {
        return $this->filesystem
            ->getDirectoryRead(DirectoryList::VAR_DIR)
            ->isExist(static::URL_REWRITE_IMPORT_DIR . '/' . $fileName);
    }
}
