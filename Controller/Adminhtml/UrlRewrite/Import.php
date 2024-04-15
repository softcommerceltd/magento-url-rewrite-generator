<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Controller\Adminhtml\UrlRewrite;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use SoftCommerce\UrlRewriteGenerator\Model\ImportExport\Mq\Publisher;

/**
 * @inheritDoc
 */
class Import extends Action implements HttpPostActionInterface
{
    /**
     * @var Publisher
     */
    private Publisher $publisher;

    /**
     * @param Publisher $publisher
     * @param Context $context
     */
    public function __construct(
        Publisher $publisher,
        Context $context
    ) {
        $this->publisher = $publisher;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->processImport();
            $resultJson->setData([
                'message' => __('Import operation has been published...'),
                'error' => false
            ]);
        } catch (\Exception $e) {
            $resultJson->setData([
                'message' => $e->getMessage(),
                'error' => true
            ]);
        }

        return $resultJson;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function processImport(): void
    {
        $sourceFile = $this->getRequest()->getParam('source_file');
        if (!$sourceFile = $sourceFile[0]['file'] ?? null) {
            return;
        }

        $this->publisher->execute($sourceFile);
    }
}
