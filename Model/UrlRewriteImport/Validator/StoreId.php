<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use SoftCommerce\Core\Model\Store\WebsiteStorageInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImportInterface;

/**
 * @inheritDoc
 */
class StoreId extends AbstractValidator
{
    /**
     * @var WebsiteStorageInterface
     */
    private WebsiteStorageInterface $websiteStorage;

    /**
     * @param WebsiteStorageInterface $websiteStorage
     */
    public function __construct(WebsiteStorageInterface $websiteStorage)
    {
        $this->websiteStorage = $websiteStorage;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->_clearMessages();

        if (empty($value[UrlRewriteImportInterface::COLUMN_NO_STORE_CODE])) {
            $this->_addMessages([
                __('Missing value for column: "%1"', UrlRewrite::REDIRECT_TYPE)
            ]);
            return false;
        }

        return !!$this->websiteStorage->getStoreCodeToId($value[UrlRewriteImportInterface::COLUMN_NO_STORE_CODE]);
    }
}
