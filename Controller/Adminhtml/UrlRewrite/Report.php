<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Controller\Adminhtml\UrlRewrite;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport;
use SoftCommerce\UrlRewriteGenerator\Model\UrlRewriteImport\FileSystem\Directory;

/**
 * @inheritDoc
 */
class Report extends Action
{
    /**
     * @inheritDoc
     */
    public const ADMIN_RESOURCE = 'Magento_UrlRewrite::urlrewrite';

    /**
     * @var FileFactory
     */
    private FileFactory $responseFileFactory;

    /**
     * @var Directory
     */
    private UrlRewriteImport\FileSystem\Directory $directory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param FileFactory $responseFileFactory
     * @param Directory $directory
     * @param LoggerInterface $logger
     * @param Context $context
     */
    public function __construct(
        FileFactory                           $responseFileFactory,
        UrlRewriteImport\FileSystem\Directory $directory,
        LoggerInterface                       $logger,
        Context                               $context
    ) {
        $this->directory = $directory;
        $this->responseFileFactory = $responseFileFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $operationId = (int) $this->getRequest()->getParam('operation');

        $fileName = sprintf(UrlRewriteImport\Report::REPORT_FILENAME_TEMPLATE, $operationId);
        if (!$this->directory->isFileExist($fileName)) {
            $this->messageManager->addErrorMessage(
                __('Report file for operation %1 does not exist', $operationId)
            );
            return $resultRedirect->setPath('adminhtml/url_rewrite/index/');
        }

        try {
            return $this->responseFileFactory->create(
                $fileName,
                ['type' => 'filename', 'value' => $this->directory->getFilePathByName($fileName)],
                DirectoryList::VAR_DIR
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('adminhtml/url_rewrite/index/');
    }
}
