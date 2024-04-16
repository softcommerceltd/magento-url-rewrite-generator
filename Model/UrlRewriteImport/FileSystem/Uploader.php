<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem;

/**
 * @inheritDoc
 */
class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * @var Directory
     */
    private Directory $directory;

    /**
     * @param Directory $directory
     * @param string $fileId
     * @param array $allowedExtensions
     */
    public function __construct(
        Directory $directory,
        string $fileId,
        array $allowedExtensions = []
    ) {
        $this->_allowedExtensions = $allowedExtensions;
        $this->directory = $directory;
        parent::__construct($fileId);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function execute(): array
    {
        return $this->save(
            $this->directory->getPath(),
            time() . '.' . $this->getFileExtension()
        );
    }
}
