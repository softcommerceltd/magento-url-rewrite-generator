<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport;

use Magento\Framework\ValidatorFactory;
use Magento\Framework\Validator;
use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Class ValidatorPool
 * used to provide a list of validation instances
 */
class ValidatorPool
{
    /**
     * @var Validator[]
     */
    private array $validators;

    /**
     * @param ValidatorFactory $validatorFactory
     * @param array $validators
     */
    public function __construct(
        private ValidatorFactory $validatorFactory,
        array $validators
    ) {
        $this->validators = $validators;
    }

    /**
     * @param string $typeId
     * @return Validator
     * @throws ConfigurationMismatchException
     */
    public function getValidator(string $typeId): Validator
    {
        if (!isset($this->validators[$typeId])) {
            throw new ConfigurationMismatchException(
                __('Could not find any validators with TypeId %1', $typeId)
            );
        }

        $validator = $this->validatorFactory->create();
        foreach ($this->validators[$typeId] as $validatorInstance) {
            $validator->addValidator($validatorInstance);
        }

        return $validator;
    }
}
