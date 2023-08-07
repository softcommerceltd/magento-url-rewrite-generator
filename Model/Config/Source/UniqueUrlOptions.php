<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @inheritDoc
 */
class UniqueUrlOptions implements OptionSourceInterface
{
    public const CATEGORY_HIGH_LEVEL = 1;
    public const CATEGORY_LOW_LEVEL = 2;
    public const LONGEST_URL = 3;
    public const SHORTEST_URL = 4;
    public const LARGEST_URL = 5;
    public const SMALLEST_URL = 6;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::CATEGORY_HIGH_LEVEL, 'label' => __('Limit to the highest category level')],
            ['value' => self::CATEGORY_LOW_LEVEL, 'label' => __('Limit to the lowest category level')],
            ['value' => self::LONGEST_URL, 'label' => __('Limit to the longest URL')],
            ['value' => self::SHORTEST_URL, 'label' => __('Limit to the shortest URL')],
            ['value' => self::LARGEST_URL, 'label' => __('Limit to the highest number of paths in the URL')],
            ['value' => self::SMALLEST_URL, 'label' => __('Limit to the lowest number of paths in the URL')]
        ];
    }
}
