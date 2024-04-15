<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Magento\UrlRewrite\Model\OptionProvider;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\ImportInterface;

/**
 * @inheritDoc
 */
class RedirectType extends AbstractValidator
{
    /**
     * @inheritDoc
     */
    public function isValid($value): bool
    {
        $this->_clearMessages();

        if (empty($value[ImportInterface::COLUMN_NO_REDIRECT_TYPE])) {
            $this->_addMessages([
                __('Missing value for column: "%1"', UrlRewrite::REDIRECT_TYPE)
            ]);
            return false;
        }

        $acceptedValues = [0, OptionProvider::PERMANENT, OptionProvider::TEMPORARY];
        if (!in_array($value[ImportInterface::COLUMN_NO_REDIRECT_TYPE], $acceptedValues)) {
            $this->_addMessages([
                __(
                    'Wrong value specified for column: "%1". Accepted values: "%2"',
                    UrlRewrite::REDIRECT_TYPE,
                    implode(', ', $acceptedValues)
                )
            ]);
            return false;
        }

        return true;
    }
}
