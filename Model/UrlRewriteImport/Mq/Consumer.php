<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\Mq;

use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use SoftCommerce\Core\Logger\LogProcessorInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImportInterface;

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
     * @var UrlRewriteImportInterface
     */
    private UrlRewriteImportInterface $urlRewriteImport;

    /**
     * @var LogProcessorInterface
     */
    private LogProcessorInterface $logger;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param EntityManager $entityManager
     * @param UrlRewriteImportInterface $urlRewriteImport
     * @param LogProcessorInterface $logger
     * @param SerializerInterface $serializer
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        EntityManager $entityManager,
        UrlRewriteImportInterface $urlRewriteImport,
        LogProcessorInterface $logger,
        SerializerInterface $serializer,
        UrlInterface $urlBuilder
    ) {
        $this->entityManager = $entityManager;
        $this->urlRewriteImport = $urlRewriteImport;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param OperationListInterface $operationList
     * @return void
     * @throws \Exception
     */
    public function execute(OperationListInterface $operationList): void
    {
        foreach ($operationList->getItems() as $operation) {
            $serializedData = $this->serializer->unserialize($operation->getSerializedData());
            if (!$fileName = $serializedData[UrlRewriteImportInterface::FILENAME] ?? null) {
                continue;
            }

            $errorCode = null;
            $status = OperationInterface::STATUS_TYPE_COMPLETE;
            $messages = [];

            $this->entityManager->save($operation);

            try {
                $this->urlRewriteImport->execute(
                    $operation->getId(),
                    $fileName,
                    (int) ($data[UrlRewriteImportInterface::ROWS_OFFSET] ?? null)
                );
            } catch (\Zend_Db_Adapter_Exception $e) {
                $this->logger->critical($e->getMessage());
                if ($e instanceof LockWaitException
                    || $e instanceof DeadlockException
                    || $e instanceof ConnectionException
                ) {
                    $errorCode = $e->getCode();
                    $message[] = __($e->getMessage());
                    $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                } else {
                    $errorCode = $e->getCode();
                    $message[] = __('Could not process URL rewrite import. Refer to logs for details.');
                    $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                }
            } catch (LocalizedException $e) {
                $errorCode = $e->getCode();
                $message[] = $e->getMessage();
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $this->logger->critical($e->getMessage());
            } catch (\Exception $e) {
                $errorCode = $e->getCode();
                $message[] = __('Could not process URL rewrite import. Refer to logs for details.');
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $this->logger->critical($e->getMessage());
            }

            foreach ($this->urlRewriteImport->getErrors() as $error) {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                if ($message = $error[UrlRewriteImportInterface::COLUMN_NO_MESSAGES] ?? '') {
                    $message .= sprintf(
                        ' [request: %s, target: %s, store: %s]',
                        $error[UrlRewriteImportInterface::COLUMN_NO_REQUEST_PATH] ?? 'n/a',
                        $error[UrlRewriteImportInterface::COLUMN_NO_TARGET_PATH] ?? 'n/a',
                        $error[UrlRewriteImportInterface::COLUMN_NO_STORE_CODE] ?? 'n/a'
                    );
                }
                $messages[] = $message;
            }

            if ($messages) {
                $messages = count($messages) > UrlRewriteImportInterface::PROCESS_PAYLOAD_BATCH
                    ? array_slice($messages, 0, UrlRewriteImportInterface::PROCESS_PAYLOAD_BATCH)
                    : $messages;

                $serializedData[UrlRewriteImportInterface::ENTITY_LINK ] = $this->urlBuilder->getUrl(
                    'softcommerce/urlRewrite/report',
                    ['operation' => $operation->getId()]
                );
            }

            $operation->setStatus($status)
                ->setErrorCode($errorCode)
                ->setSerializedData($this->serializer->serialize($serializedData))
                ->setResultMessage($this->serializer->serialize($messages));

            $this->entityManager->save($operation);
        }
    }
}
