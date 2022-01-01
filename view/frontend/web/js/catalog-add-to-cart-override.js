/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/translate',
    'underscore',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Catalog/js/product/view/product-info-resolver',
    'jquery-ui-modules/widget',
    'mage/url',
    'Magento_Ui/js/model/messageList'
], function ($, $t, _, idsResolver, productInfoResolver,urlBuilder,messageList) {
    'use strict';

    $.widget('mage.catalogAddToCart', {
        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            addToCartButtonSelector: '.action.tocart',
            addToCartButtonDisabledClass: 'disabled',
            addToCartButtonTextWhileAdding: '',
            addToCartButtonTextAdded: '',
            addToCartButtonTextDefault: '',
            productInfoResolver: productInfoResolver
        },

        /** @inheritdoc */
        _create: function () {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
        },

        /**
         * @private
         */
        _bindSubmit: function () {
            var self = this;

            if (this.element.data('catalog-addtocart-initialized')) {
                return;
            }

            this.element.data('catalog-addtocart-initialized', 1);
            this.element.on('submit', function (e) {
                e.preventDefault();
                self.submitForm($(this));
            });
        },

        /**
         * @private
         */
        _redirect: function (url) {
            var urlParts, locationParts, forceReload;

            urlParts = url.split('#');
            locationParts = window.location.href.split('#');
            forceReload = urlParts[0] === locationParts[0];

            window.location.assign(url);

            if (forceReload) {
                window.location.reload();
            }
        },

        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },

        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        // submitForm: function (form) {
        //     this.ajaxSubmit(form);
        // },
        submitForm: function (form) {
            var self = this;
            var urlGetAttribute = $('input[name="url_get_attribute_order_approval_rule"]').val();
            var selectedConfigurableOption = $('input[name="selected_configurable_option"]').val();
            var skuProduct = form.context.attributes['data-product-sku'].value;
            var enterQty = $('input[id="qty"]').val();
            if(urlGetAttribute){
                $.ajax({
                    type: "POST",
                    url: urlGetAttribute,
                    data: {
                        'skuProduct':  skuProduct,
                        'enterQty': enterQty,
                        'selectedConfigurableOption': selectedConfigurableOption
                    },
                    dataType: 'json',
                    success: function (response) {
                        if(!response.message){
                            $(self.options.messagesSelector).html('<div class="allocation-message-error">'+response.notice+'</div>');
                            setTimeout(function(){
                                $(self.options.messagesSelector).html('');
                            }, 3000);
                        }else{
                            self.ajaxSubmit(form);
                        }
                    }
                });
            }else{
                self.ajaxSubmit(form);
            }
        },


        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                productIds = idsResolver(form),
                productInfo = self.options.productInfoResolver(form),
                formData;

            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);
            formData = new FormData(form[0]);

            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;

                    $(document).trigger('ajax:addToCart', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });

                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        eventData = {
                            'form': form,
                            'redirectParameters': []
                        };
                        // trigger global event, so other modules will be able add parameters to redirect url
                        $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                        if (eventData.redirectParameters.length > 0 &&
                            window.location.href.split(/[?#]/)[0] === res.backUrl
                        ) {
                            parameters = res.backUrl.split('#');
                            parameters.push(eventData.redirectParameters.join('&'));
                            res.backUrl = parameters.join('#');
                        }

                        self._redirect(res.backUrl);

                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                    //popup code start
                    // self.checkProductNeedApproval(form);
                    //    //popup code end
                },


                /** @inheritdoc */
                error: function (res) {
                    $(document).trigger('ajax:addToCart:error', {
                        'sku': form.data().productSku,
                        'productIds': productIds,
                        'productInfo': productInfo,
                        'form': form,
                        'response': res
                    });
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            });
        },

        checkProductNeedApproval: function(form)
        {
            var self = this;
            var urlGetAttribute = $('input[name="url_get_attribute_order_approval_rule"]').val();
            var skuProduct = form.context.attributes['data-product-sku'].value;
            $.ajax({
                type: "POST",
                url: urlGetAttribute,
                data: {
                    'skuProduct':  skuProduct
                },
                dataType: 'json',
                success: function (response) {
                    if(response.message){
                        self.showPopupApprovalRule();
                    }
                }
            });
        },

        showPopupApprovalRule: function()
        {
            var popup = $('<div class="add-to-cart-dialog"/>').html($t('<span>This product is a release item. The delivery takes place only after approval by the supervisor.</span>')).modal({
                modalClass: 'add-to-cart-popup',
                //title: $.mage.__("No Title"),
                buttons: [
                    {
                        text: $t('Continue Shopping'),
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $t('Cancel'),
                        click: function () {
                            this.closeModal();
                        }
                    }
                ]
            });
            popup.modal('openModal');
        },

        // showPopupApprovalRule: function()
        // {
        //     var popup = $('<div class="add-to-cart-dialog"/>').html($t('<span>This product is a release item. The delivery takes place only after approval by the supervisor.</span>')).modal({
        //         modalClass: 'add-to-cart-popup',
        //         //title: $.mage.__("No Title"),
        //         buttons: [
        //             {
        //                 text: $t('Continue Shopping'),
        //                 click: function () {
        //                     this.closeModal();
        //                 }
        //             },
        //             {
        //                 text: $t('Proceed to Checkout'),
        //                 click: function () {
        //                     window.location = window.checkout.checkoutUrl
        //                 }
        //             }
        //         ]
        //     });
        //     popup.modal('openModal');
        // },
        /**
         * @param {String} form
         */
        disableAddToCartButton: function (form) {
            var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...'),
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
            addToCartButton.attr('title', addToCartButtonTextWhileAdding);
        },

        /**
         * @param {String} form
         */
        enableAddToCartButton: function (form) {
            var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added'),
                self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.find('span').text(addToCartButtonTextAdded);
            addToCartButton.attr('title', addToCartButtonTextAdded);

            setTimeout(function () {
                var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart');

                addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextDefault);
                addToCartButton.attr('title', addToCartButtonTextDefault);
            }, 1000);
        }
    });

    return $.mage.catalogAddToCart;
});
