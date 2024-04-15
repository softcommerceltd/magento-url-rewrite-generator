<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Model\ImportExport;

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
use Magento\Framework\Exception\LocalizedException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\FileSystem\Pool;

/**
 * @inheritDoc
 */
class Import implements ImportInterface
{
    const XXX = 'web/url/use_store';
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
        StoreRepositoryInterface $storeRepository,
        UrlFinderInterface $urlFinder,
        UrlPersistInterface $urlPersist,
        UrlRewriteFactory $urlRewriteFactory,
        ValidatorPool $validatorPool
    ) {
        $this->filePool = $filePool;
        $this->report = $report;
        $this->mergeUrlDataProviderFactory = $mergeUrlDataProviderFactory;
        $this->storeRepository = $storeRepository;
        $this->urlFinder = $urlFinder;
        $this->urlPersist = $urlPersist;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->validatorPool = $validatorPool;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $operationId, string $filename, int $offset, int $size): void
    {
        $this->errors = [];
        $file = $this->filePool->get($filename);
        $rows = $file->getRows($offset, $size);

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
            $this->report->save($operationId, $this->errors);
            throw new LocalizedException(__('Import processed with errors.'));
        }
    }

    /**
     * @param array $row
     * @return UrlRewrite|null
     * @throws ConfigurationMismatchException
     * @throws NoSuchEntityException
     */
    private function generateUrlRewriteRequest(array $row): ?UrlRewrite
    {
        $row = $this->prepareUrlPaths($row);

        if (!$this->getValidator()->isValid($row)) {
            $row[self::COLUMN_NO_MESSAGES] = __(
                'The row isn\'t valid. Reason(s): %1',
                implode('; ', $this->getValidator()->getMessages())
            );
            $this->errors[] = $row;
            return null;
        }

        $requestUrl = $this->urlRewriteFactory->create()
            ->setEntityType('custom')
            ->setEntityId(0)
            ->setRequestPath($row[self::COLUMN_NO_REQUEST_PATH])
            ->setTargetPath($row[self::COLUMN_NO_TARGET_PATH])
            ->setRedirectType($row[self::COLUMN_NO_REDIRECT_TYPE])
            ->setStoreId($this->getStoreId($row))
            ->setDescription($row[self::COLUMN_NO_DESCRIPTION] ?? '')
            ->setIsAutogenerated(false)
            ->setMetadata($row[self::COLUMN_NO_METADATA] ?? '');

        $currentUrl = $this->urlFinder->findOneByData([
            UrlRewrite::ENTITY_TYPE => 'custom',
            UrlRewrite::REDIRECT_TYPE => $row[self::COLUMN_NO_REDIRECT_TYPE],
            UrlRewrite::REQUEST_PATH => $row[self::COLUMN_NO_REQUEST_PATH],
            UrlRewrite::STORE_ID => $row[self::COLUMN_NO_STORE_CODE]
        ]);

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
        if (empty($row[self::COLUMN_NO_TARGET_PATH])
            || empty($row[self::COLUMN_NO_STORE_CODE])
        ) {
            return '';
        }

        $path = $this->parseUrlPath(
            $row[self::COLUMN_NO_TARGET_PATH],
            $row[self::COLUMN_NO_STORE_CODE]
        );

        if ($path !== '') {
            return $path;
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

        $path = str_replace(
            [$store->getBaseUrl(UrlInterface::URL_TYPE_WEB), $store->getCode()],
            '', $path
        );

        return ltrim($path, '/');
    }
}
