define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($, confirm, alert, $t,customerData, urlBuilder) {
    'use strict';
    $.widget(
        'mage.product_detail_show_limit',
        {
            _create: function () {
                var self = this;
                var options = self.options;
                $(document).ready(function(){
                    $("select.super-attribute-select").change(function(){
                        var product_id = $('input[name="selected_configurable_option"]').val();
                        console.log(product_id);
                        self.ajaxCheckQty(options,product_id);
                    });
                });

            },
            ajaxCheckQty: function (options,product_id) {
                $.ajax({
                    url : options.ajax_url,
                    data : {
                        product_id: product_id
                    },
                    type : 'post',
                    dataType : 'json',
                    showLoader: true,
                    beforeSend : function(){
                        $('#loading-mask').show();
                    },
                    success: function (response) {
                        var html = '';
                        if(response.display){
                            html += '<p>'+$t('My quota:')+'<span id="qty_limit">'+ ' '+ response.qty_product_allocation +'</span></p>';
                            $('.productlimit-allocation').html(html);
                        }else{
                            $('.productlimit-allocation').html('');
                        }
                    },
                    complete : function(){
                        $('#loading-mask').hide();
                    }
                });

            },
        }
    );
    return $.mage.product_detail_show_limit;
});