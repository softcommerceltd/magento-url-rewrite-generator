<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\Mq;

use Magento\AsynchronousOperations\Api\Data\OperationListInterfaceFactory;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\MessageQueue\MergerInterface;

/**
 * Class Merger
 * used to merge messages from the message queue
 */
class Merger implements MergerInterface
{
    /**
     * @var OperationListInterfaceFactory
     */
    private OperationListInterfaceFactory $operationListFactory;

    /**
     * @var MergedMessageInterfaceFactory
     */
    private MergedMessageInterfaceFactory $mergedMessageFactory;

    /**
     * @param OperationListInterfaceFactory $operationListFactory
     * @param MergedMessageInterfaceFactory $mergedMessageFactory
     */
    public function __construct(
        OperationListInterfaceFactory $operationListFactory,
        MergedMessageInterfaceFactory $mergedMessageFactory
    ) {
        $this->operationListFactory = $operationListFactory;
        $this->mergedMessageFactory = $mergedMessageFactory;
    }

    /**
     * @inheritdoc
     */
    public function merge(array $messages): array
    {
        $result = [];
        foreach ($messages as $topic => $message) {
            $operationList = $this->operationListFactory->create(['items' => $message]);
            $messagesIds = array_keys($message);
            $result[$topic][] = $this->mergedMessageFactory->create(
                [
                    'mergedMessage' => $operationList,
                    'originalMessagesIds' => $messagesIds
                ]
            );
        }

        return $result;
    }
}
