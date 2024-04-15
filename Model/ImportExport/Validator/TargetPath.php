<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\ImportInterface;

/**
 * @inheritDoc
 */
class TargetPath extends AbstractValidator
{
    /**
     * @inheritDoc
     */
    public function isValid($value): bool
    {
        $this->_clearMessages();

        if (empty($value[ImportInterface::COLUMN_NO_TARGET_PATH])) {
            $this->_addMessages([
                __('Missing value for column: "%1"', UrlRewrite::TARGET_PATH)
            ]);
            return false;
        }

        return true;
    }
}
