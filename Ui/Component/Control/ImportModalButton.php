<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\UrlRewriteGenerator\Ui\Component\Control;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * @inheritDoc
 */
class ImportModalButton implements ButtonProviderInterface
{
    private const NS = 'url_rewrite_listing';

    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        $targetModal = self::NS . '.' . self::NS . '.url_rewrite_import_form_modal.form_modal';
        $targetForm = self::NS . '.' . self::NS . '.url_rewrite_import_form_modal.form_modal.softcommerce_urlrewrite_import_form';

        return [
            'label' => __('Import URL Rewrites'),
            'class' => 'action secondary',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => $targetForm,
                                'actionName' => 'destroyInserted'
                            ],
                            [
                                'targetName' => $targetModal,
                                'actionName' => 'openModal'
                            ],
                            [
                                'targetName' => $targetForm,
                                'actionName' => 'render'
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => '',
            'sort_order' => 10
        ];
    }
}
