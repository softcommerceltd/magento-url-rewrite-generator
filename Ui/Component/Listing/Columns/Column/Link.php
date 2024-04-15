<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Ui\Component\Listing\Columns\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * @inheritDoc
 */
class Link extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item['link'])) {
                $item['link'] = $this->context->getUrl($item['link']);
            }
        }

        return $dataSource;
    }
}
