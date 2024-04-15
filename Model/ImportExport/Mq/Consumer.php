<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Mq;

use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\ImportInterface;

/**
 * Class Consumer
 * used to run import url rewrites
 */
class Consumer
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var ImportInterface
     */
    private ImportInterface $import;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var OperationManagementInterface
     */
    private OperationManagementInterface $operationManagement;

    /**
     * @param EntityManager $entityManager
     * @param ImportInterface $import
     * @param OperationManagementInterface $operationManagement
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        EntityManager $entityManager,
        ImportInterface $import,
        OperationManagementInterface $operationManagement,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->import = $import;
        $this->operationManagement = $operationManagement;
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * @param OperationListInterface $operationList
     * @return void
     * @throws LocalizedException
     */
    public function execute(OperationListInterface $operationList): void
    {
        foreach ($operationList->getItems() as $operation) {
            $errorCode = null;
            $status = OperationInterface::STATUS_TYPE_COMPLETE;
            $message = null;
            $data = $this->serializer->unserialize($operation->getSerializedData());

            try {
                $this->entityManager->save($operation);
            } catch (\Exception $e) {
                $this->logger->error($e);
                throw new LocalizedException(
                    __('Could not save an operation. Refer to log for more details.'),
                    $e
                );
            }

            try {
                $this->import->execute(
                    $operation->getId(),
                    $data[ImportInterface::FILENAME],
                    $data[ImportInterface::OFFSET],
                    $data[ImportInterface::SIZE]
                );
            } catch (LocalizedException $e) {
                $data['entity_link'] = 'softcommerce/urlRewrite/report/operation/' . $operation->getId();
                $message = $e->getMessage();
                $errorCode = $e->getCode();
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            }

            $this->operationManagement->changeOperationStatus(
                $operation->getId(),
                $status,
                $errorCode,
                $message,
                $this->serializer->serialize($data)
            );
        }
    }
}
