<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use SoftCommerce\Core\Framework\DataStorageInterface;
use SoftCommerce\Core\Framework\MessageStorageInterface;

/**
 * Interface UrlRewriteInterface used to
 * generate URL rewrites for a given entity.
 */
interface UrlRewriteInterface
{
    /**
     * @return DataStorageInterface
     */
    public function getResponseStorage(): DataStorageInterface;

    /**
     * @return MessageStorageInterface
     */
    public function getMessageStorage(): MessageStorageInterface;

    /**
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds): void;
}
