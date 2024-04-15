/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/components/insert-form',
    'mage/backend/notification'
], function (jQuery, Insert) {
    'use strict';

    return Insert.extend({
        defaults: {
            listens: {
                responseData: 'onResponse'
            },
            modules: {
                listingProvider: '${ $.listingProvider }',
                formModalProvider: '${ $.formModalProvider }',
                notificationListing: '${ $.columnsProvider }'
            }
        },

        /**
         * @param {Object} response
         */
        onResponse: function (response) {
            if (!response.error) {
                this.formModalProvider().closeModal();
                this.notificationListing().reload();
                this.listingProvider().reload({
                    refresh: true
                });
            }

            if (response.message) {
                jQuery('body').notification('clear')
                    .notification('add', {
                        error: response.error,
                        message: response.message,
                        /**
                         * @param {String} message
                         */
                        insertMethod: function (message) {
                            const element = jQuery('<div></div>').html(message);
                            jQuery('.page-content .page-main-actions').after(element);
                        }
                    });
            }
        },
    });
});
