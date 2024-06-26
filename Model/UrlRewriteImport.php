<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model;

use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\MergeDataProviderFactory;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use SoftCommerce\Core\Logger\LogProcessorInterface;
use SoftCommerce\Core\Model\Source\StatusInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem\Pool;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\Report;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\ValidatorPool;

/**
 * @inheritDoc
 */
class UrlRewriteImport implements UrlRewriteImportInterface
{
    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @var FileSystem\Pool
     */
    private FileSystem\Pool $filePool;

    /**
     * @var MergeDataProviderFactory
     */
    private MergeDataProviderFactory $mergeUrlDataProviderFactory;

    /**
     * @var LogProcessorInterface
     */
    private LogProcessorInterface $logger;

    /**
     * @var UrlFinderInterface
     */
    private UrlFinderInterface $urlFinder;

    /**
     * @var UrlPersistInterface
     */
    private UrlPersistInterface $urlPersist;

    /**
     * @var Report
     */
    private Report $report;

    /**
     * @var array
     */
    private array $storeInMemory = [];

    /**
     * @var StoreRepositoryInterface
     */
    protected StoreRepositoryInterface $storeRepository;

    /**
     * @var UrlRewriteFactory
     */
    private UrlRewriteFactory $urlRewriteFactory;

    /**
     * @var Validator|null
     */
    private ?Validator $validator = null;

    /**
     * @var ValidatorPool
     */
    private ValidatorPool $validatorPool;

    /**
     * @param Pool $filePool
     * @param Report $report
     * @param MergeDataProviderFactory $mergeUrlDataProviderFactory
     * @param LogProcessorInterface $logger
     * @param StoreRepositoryInterface $storeRepository
     * @param UrlFinderInterface $urlFinder
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param ValidatorPool $validatorPool
     */
    public function __construct(
        FileSystem\Pool $filePool,
        Report $report,
        MergeDataProviderFactory $mergeUrlDataProviderFactory,
        LogProcessorInterface $logger,
        StoreRepositoryInterface $storeRepository,
        UrlFinderInterface $urlFinder,
        UrlPersistInterface $urlPersist,
        UrlRewriteFactory $urlRewriteFactory,
        ValidatorPool $validatorPool
    ) {
        $this->filePool = $filePool;
        $this->report = $report;
        $this->mergeUrlDataProviderFactory = $mergeUrlDataProviderFactory;
        $this->logger = $logger;
        $this->storeRepository = $storeRepository;
        $this->urlFinder = $urlFinder;
        $this->urlPersist = $urlPersist;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->validatorPool = $validatorPool;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $operationId, string $filename, int $rowsOffset): void
    {
        $this->errors = [];
        $file = $this->filePool->get($filename);
        $rows = $file->getRows($rowsOffset, self::PROCESS_ROW_BATCH);

        if (isset($rows[0][self::COLUMN_NO_REQUEST_PATH])
            && $rows[0][self::COLUMN_NO_REQUEST_PATH] === UrlRewrite::REQUEST_PATH) {
            unset($rows[0]);
        }

        $request = [];
        foreach ($rows as $row) {
            if ($urlRewrite = $this->generateUrlRewriteRequest($row)) {
                $request[] = $urlRewrite;
            }
        }

        foreach (array_chunk($request, self::PROCESS_PAYLOAD_BATCH) as $payload) {
            try {
                $this->importUrlRewriteRequest($payload);
            } catch (\Exception $e) {
                $this->errors[] = [
                    self::COLUMN_NO_MESSAGES => $e->getMessage()
                ];
            }
        }

        if ($this->errors) {
            $this->logger->execute(StatusInterface::ERROR, $this->errors);
        }
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $row
     * @return UrlRewrite|null
     * @throws ConfigurationMismatchException
     * @throws NoSuchEntityException
     */
    private function generateUrlRewriteRequest(array $row): ?UrlRewrite
    {
        if (!$this->isRowColumnsValid($row)) {
            return null;
        }

        $row = $this->prepareUrlPaths($row);

        if (!$this->getValidator()->isValid($row)) {
            $row[self::COLUMN_NO_MESSAGES] = implode(
                self::ERROR_MSG_DELIMITER,
                $this->getValidator()->getMessages()
            );
            $this->errors[] = $row;
            return null;
        }

        $requestPath = $row[self::COLUMN_NO_REQUEST_PATH];
        $redirectType = $row[self::COLUMN_NO_REDIRECT_TYPE];
        $storeId = $this->getStoreId($row);

        $currentUrl = $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => $requestPath,
            UrlRewrite::STORE_ID => $storeId
        ]);

        if ($currentUrl && $currentUrl->getEntityType() !== 'custom') {
            return null;
        }

        $requestUrl = $this->urlRewriteFactory->create()
            ->setUrlRewriteId(0)
            ->setEntityType('custom')
            ->setEntityId(0)
            ->setRequestPath($requestPath)
            ->setTargetPath($row[self::COLUMN_NO_TARGET_PATH])
            ->setRedirectType($redirectType)
            ->setStoreId($storeId)
            ->setDescription($row[self::COLUMN_NO_DESCRIPTION] ?? '')
            ->setIsAutogenerated(false)
            ->setMetadata($row[self::COLUMN_NO_METADATA] ?? '');

        if ($currentUrl) {
            $requestUrl->setUrlRewriteId($currentUrl->getUrlRewriteId());
        }

        return $requestUrl;
    }

    /**
     * @param array $row
     * @return array
     * @throws NoSuchEntityException
     */
    private function prepareUrlPaths(array $row): array
    {
        $row[self::COLUMN_NO_REQUEST_PATH] = $this->getRequestPath($row);
        $row[self::COLUMN_NO_TARGET_PATH] = $this->getTargetPath($row);
        return $row;
    }

    /**
     * @param array $request
     * @return void
     * @throws UrlAlreadyExistsException
     */
    private function importUrlRewriteRequest(array $request): void
    {
        $mergeUrlDataProvider = $this->mergeUrlDataProviderFactory->create();
        $mergeUrlDataProvider->merge($request);

        if ($urlData = $mergeUrlDataProvider->getData()) {
            $this->urlPersist->replace($urlData);
        }
    }

    /**
     * @return Validator
     * @throws ConfigurationMismatchException
     */
    private function getValidator(): Validator
    {
        if (null === $this->validator) {
            $this->validator = $this->validatorPool->getValidator('import');
        }
        return $this->validator;
    }

    /**
     * @param array $row
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStoreId(array $row): int
    {
        return (int) $this->getStore($row[self::COLUMN_NO_STORE_CODE])->getId();
    }

    /**
     * @param string $storeCode
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore(string $storeCode): StoreInterface
    {
        $storeCode = strtolower($storeCode);

        if (!isset($this->storeInMemory[$storeCode])) {
            $this->storeInMemory[$storeCode] = $this->storeRepository->get($storeCode);
        }

        return $this->storeInMemory[$storeCode];
    }

    /**
     * @param array $row
     * @return string
     * @throws NoSuchEntityException
     */
    private function getRequestPath(array $row): string
    {
        return $this->parseUrlPath(
            $row[self::COLUMN_NO_REQUEST_PATH] ?? '',
            $row[self::COLUMN_NO_STORE_CODE] ?? ''
        );
    }

    /**
     * @param array $row
     * @return string
     * @throws NoSuchEntityException
     */
    private function getTargetPath(array $row): string
    {
        $path = $this->parseUrlPath(
            $row[self::COLUMN_NO_TARGET_PATH],
            $row[self::COLUMN_NO_STORE_CODE]
        );

        if ($path !== '') {
            return rtrim($path, '/');
        }

        $path = '../';
        $store = $this->getStore($row[self::COLUMN_NO_STORE_CODE]);

        if ($store->isUseStoreInUrl()) {
            $path .= $store->getCode();
        }

        return $path;
    }

    /**
     * @param string $path
     * @param string $storeCode
     * @return string
     * @throws NoSuchEntityException
     */
    private function parseUrlPath(string $path, string $storeCode): string
    {
        $store = $this->getStore($storeCode);

        $storeUrl = $store->getBaseUrl();
        $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        $resultPath = str_replace($storeUrl, '', $path);

        if ($resultPath !== $path) {
            return ltrim($resultPath, '/');
        }

        if ($storeUrl !== $storeBaseUrl) {
            $resultPath = str_replace($storeBaseUrl, '', $path);
        }

        return ltrim($resultPath, '/');
    }

    /**
     * @param array $row
     * @return bool
     */
    private function isRowColumnsValid(array $row): bool
    {
        $errors = [];
        if (empty($row[self::COLUMN_NO_REQUEST_PATH])) {
            $errors[] = __(self::ERROR_MSG_MISSING_COLUMN_VALUE, UrlRewrite::REQUEST_PATH);
        }

        if (empty($row[self::COLUMN_NO_TARGET_PATH])) {
            $errors[] = __(self::ERROR_MSG_MISSING_COLUMN_VALUE, UrlRewrite::TARGET_PATH);
        }

        if (empty($row[self::COLUMN_NO_REDIRECT_TYPE])) {
            $errors[] = __(self::ERROR_MSG_MISSING_COLUMN_VALUE, UrlRewrite::REDIRECT_TYPE);
        }

        if (empty($row[self::COLUMN_NO_STORE_CODE])) {
            $errors[] = __(self::ERROR_MSG_MISSING_COLUMN_VALUE, UrlRewrite::REQUEST_PATH);
        }

        if ($errors) {
            $row[self::COLUMN_NO_MESSAGES] = implode( self::ERROR_MSG_DELIMITER, $errors);
            $this->errors[] = $row;
        }

        return !$errors;
    }
}
