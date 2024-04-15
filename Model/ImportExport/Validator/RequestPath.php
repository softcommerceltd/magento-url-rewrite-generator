<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Magento\UrlRewrite\Helper\UrlRewrite as UrlRewriteHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\ImportInterface;

/**
 * @inheritDoc
 */
class RequestPath extends AbstractValidator
{
    /**
     * @var UrlRewriteHelper
     */
    private UrlRewriteHelper $urlRewriteHelper;

    /**
     * @param UrlRewriteHelper $urlRewriteHelper
     */
    public function __construct(UrlRewriteHelper $urlRewriteHelper)
    {
        $this->urlRewriteHelper = $urlRewriteHelper;
    }

    /**
     * @inheritDoc
     */
    public function isValid($value): bool
    {
        $this->_clearMessages();

        if (empty($value[ImportInterface::COLUMN_NO_REQUEST_PATH])) {
            $this->_addMessages([
                __('Missing value for column: "%1"', UrlRewrite::REQUEST_PATH)
            ]);
            return false;
        }

        try {
            return $this->urlRewriteHelper->validateRequestPath($value[ImportInterface::COLUMN_NO_REQUEST_PATH]);
        } catch (LocalizedException $e) {
            $this->_addMessages([$e->getMessage()]);
            return false;
        }
    }
}
