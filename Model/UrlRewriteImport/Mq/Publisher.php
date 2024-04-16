<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\Mq;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem\Pool;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImportInterface;

/**
 * Class Publisher
 * used to schedule operations of import url rewrites
 */
class Publisher
{
    /**
     * @var BulkManagementInterface
     */
    private BulkManagementInterface $bulkManagement;

    /**
     * @var IdentityGeneratorInterface
     */
    private IdentityGeneratorInterface $identityService;

    /**
     * @var OperationInterfaceFactory
     */
    private OperationInterfaceFactory $operationFactory;

    /**
     * @var Pool
     */
    private Pool $filePool;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param OperationInterfaceFactory $operationFactory
     * @param Pool $filePool
     * @param SerializerInterface $serializer
     * @param UserContextInterface $userContextInterface
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        OperationInterfaceFactory $operationFactory,
        Pool $filePool,
        SerializerInterface $serializer,
        UserContextInterface $userContextInterface
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->identityService = $identityService;
        $this->operationFactory = $operationFactory;
        $this->filePool = $filePool;
        $this->serializer = $serializer;
        $this->userContext = $userContextInterface;
    }

    /**
     * @param string $filename
     * @return void
     * @throws LocalizedException
     */
    public function execute(string $filename): void
    {
        $objectFile = $this->filePool->get($filename);

        if (!$count = $objectFile->getRowsCount()) {
            return;
        }

        $operationCount = ceil($count / UrlRewriteImportInterface::PROCESS_ROW_BATCH);
        $bulkUuid = $this->identityService->generateId();
        $request = [];

        while ($operationCount > 0) {
            $operationCount--;
            $rowsOffset = $operationCount * UrlRewriteImportInterface::PROCESS_ROW_BATCH;

            $serializedData = [
                UrlRewriteImportInterface::ROWS_OFFSET => $rowsOffset,
                UrlRewriteImportInterface::META_INFORMATION => 'Rows offset: ' . $rowsOffset,
                UrlRewriteImportInterface::FILENAME => $filename,
            ];

            $data = [
                'data' => [
                    OperationInterface::BULK_ID => $bulkUuid,
                    OperationInterface::TOPIC_NAME => 'url.rewrite.import.processor',
                    OperationInterface::SERIALIZED_DATA => $this->serializer->serialize($serializedData),
                    OperationInterface::STATUS => OperationInterface::STATUS_TYPE_OPEN,
                ]
            ];

            $request[] = $this->operationFactory->create($data);
        }

        if (!$request) {
            return;
        }

        $result = $this->bulkManagement->scheduleBulk(
            $bulkUuid,
            $request,
            __('URL Rewrites Import.'),
            $this->userContext->getUserId()
        );

        if (!$result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }
    }
}
