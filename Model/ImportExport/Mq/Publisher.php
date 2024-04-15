<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Mq;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\FileSystem\Pool;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\ImportInterface;

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
     * @param UserContextInterface $userContextInterface
     * @param SerializerInterface $serializer
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        IdentityGeneratorInterface $identityService,
        OperationInterfaceFactory $operationFactory,
        Pool $filePool,
        UserContextInterface $userContextInterface,
        SerializerInterface $serializer
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->identityService = $identityService;
        $this->operationFactory = $operationFactory;
        $this->filePool = $filePool;
        $this->userContext = $userContextInterface;
        $this->serializer = $serializer;
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

        $rows = ceil($count / ImportInterface::PROCESS_ROW_BATCH);
        $uUid = $this->identityService->generateId();
        $request = [];

        while ($rows > 0) {
            $rows--;
            $offset = $rows * ImportInterface::PROCESS_ROW_BATCH;

            $serializedData = [
                ImportInterface::FILENAME => $filename,
                ImportInterface::OFFSET => $offset,
                ImportInterface::SIZE => ImportInterface::PROCESS_ROW_BATCH,
                UrlRewrite::METADATA => 'The offset of rows: ' . $offset,
            ];

            $data = [
                'data' => [
                    OperationInterface::BULK_ID => $uUid,
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
            $uUid,
            $request,
            'Import of URL Rewrites.',
            $this->userContext->getUserId()
        );


        if (!$result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }
    }
}
