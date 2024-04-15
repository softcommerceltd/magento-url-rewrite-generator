<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

/**
 * @inheritDoc
 */
class UrlRewriteImportDataProvider extends ModifierPoolDataProvider
{
    private const KEY_SUBMIT_URL = 'submit_url';

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        UrlInterface $urlBuilder,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter): void
    {
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        $this->data['config'] = [
            self::KEY_SUBMIT_URL => $this->urlBuilder->getUrl('softcommerce/urlRewrite/import')
        ];

        return parent::getConfigData();
    }
}
