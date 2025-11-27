<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Controller\Adminhtml\UrlRewrite;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem\UploaderFactory;

/**
 * @inheritDoc
 */
class Upload extends Action
{
    /**
     * @inheritDoc
     */
    public const ADMIN_RESOURCE = 'Magento_UrlRewrite::urlrewrite';

    /**
     * @param UploaderFactory $fileUploaderFactory
     * @param LoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        private UploaderFactory $fileUploaderFactory,
        private LoggerInterface $logger,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $fileUploader = $this->fileUploaderFactory->create(['fileId' => 'source_file']);
            $result = $fileUploader->execute();
            unset($result['tmp_name'], $result['path']);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
