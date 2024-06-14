<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

/**
 * Interface GetProductEntityDataInterface
 * used to retrieve data from catalog_product_entity table.
 */
interface GetProductEntityDataInterface
{
    /**
     * @param array $productIds
     * @return array
     * @throws \Exception
     */
    public function execute(array $productIds = []): array;
}
