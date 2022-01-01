define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'Magento_Customer/js/customer-data'
], function ($, confirm, alert, $t,customerData) {
    'use strict';
    $.widget(
        'mage.productallocation_manage',
        {
            _create: function () {
                var self = this;
                var options = self.options;
                $("#search_btn").click(function(){
                    self.filter(options);
                });
                $(document).on('change', 'select.super-attribute', function(){
                    var product_id = $(this).val();
                    var index_item = $(this).data('index');
                    self.selectProductSimpleData(product_id,options.ajax_child_product_configurable,index_item);
                });

                self.searchInputEnter(options);
                $(document).on('click', '.add-to-cart.button', function(){
                    var allocation_id = $(this).attr('data-allocation-id');
                    var qty = $("#qty_to_cart_"+allocation_id).val();
                    var url_add_to_cart = $("#js-url-add-to-cart").val();
                    var index_item = $(this).data('index');
                    $.ajax({
                        url : url_add_to_cart,
                        data : {
                            allocation_id: allocation_id,
                            qty: qty
                        },
                        type : 'post',
                        dataType : 'json',
                        showLoader: true,
                        beforeSend : function(){
                            $('#loading-mask').show();
                        },
                        success: function (response) {
                            var div_allocation_action = '.list-item-'+index_item+ ' ' + '.list-action-'+index_item+ ' ';
                            if(response.is_error){
                                $(div_allocation_action + '.label.label-message').html(response.notice);
                            }else{
                                $(div_allocation_action + '.label.label-message').html('');
                                if(response.message){
                                    var sections = ['cart'];
                                    customerData.invalidate(sections);
                                    customerData.reload(sections, true);
                                    $("div[data-placeholder=messages]").html('<div class="allocation-message-success">'+response.notice+'</div>');
                                }else{
                                    $("div[data-placeholder=messages]").html('<div class="allocation-message-error">'+response.notice+'</div>');
                                }
                                setTimeout(function(){
                                    $("div[data-placeholder=messages]").html('');
                                }, 3000);
                            }

                        },
                        complete : function(){
                            $('#loading-mask').hide();
                        }
                    });
                });
            },
            selectProductSimpleData: function(product_id,url_ajax_child_product_configurable,index_item){
                $.ajax({
                    url : url_ajax_child_product_configurable,
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
                        var div_show_qty = $('.list-item-'+index_item + ' ' + '.list-product-allocation-qty span');
                        var div_allocation_action = '.list-item-'+index_item+ ' ' + '.list-action' + ' ';
                        $(div_allocation_action + '.add-to-cart.button').attr('data-allocation-id',response.allocation_id);
                        $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('id','qty_to_cart_'+response.allocation_id);
                        $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('name','qty_to_cart['+response.allocation_id+']');
                        if(response.is_has_qty_allocation){
                            div_show_qty.html(response.qty_allocation);
                            $(div_allocation_action + '.add-to-cart.button').removeClass('disabled');
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('min',response.qty_min_sale);
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('value',response.qty_min_sale);
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').prop("readonly",false);
                        }else{
                            div_show_qty.html('');
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('min','');
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').attr('value','');
                            $(div_allocation_action + '.input-add-to-cart.input-add-my-orders').prop("readonly",true);
                            $(div_allocation_action + '.add-to-cart.button').addClass('disabled');
                        }
                    },
                    complete : function(){
                        $('#loading-mask').hide();
                    }
                });

            },
            searchInputEnter : function (options){
                var self = this;
                $(document).keypress(function(e){
                    var keycode = (e.keyCode ? e.keyCode : e.which);
                    if(keycode == '13'){
                        self.filter(options);
                    }
                    // var fieldEmpty = true;
                    // var data = [];
                    // $(".filter").each(function(){
                    //     data[$(this).attr('name')] = $(this).val();
                    // });
                    // if(data["sku"] !== '' || data["name"] !== '' || data["from_quantity"] !== '' || data["to_quantity"] !== ''){
                    //     fieldEmpty = false;
                    // }
                    // if(data["sku"] === '' || data["name"] === '' || data["from_quantity"] === '' || data["to_quantity"] === ''){
                    //     fieldEmpty = false;
                    // }

                    // if(keycode == '13' && !fieldEmpty){
                    //     self.filter(options);
                    // }
                });

            },

            filter: function (options){
                var data = [];
                $(".filter").each(function(){
                    data[$(this).attr('name')] = $(this).val();
                });
                var current_customer_email = $('#js-current-customer-email').val();
                $.ajax({
                    url : options.ajax_url,
                    data : {
                        current_customer_email: current_customer_email,
                        sku: data["sku"],
                        name: data["name"],
                        from_quantity: data["from_quantity"],
                        to_quantity: data["to_quantity"]
                    },
                    type : 'post',
                    dataType : 'json',
                    showLoader: true,
                    beforeSend: function(){
                        $('#loading-mask').show();
                    },
                    success: function(response){
                        var html = '';
                        $("#my-orders-table tbody").html('');
                        if(response.count > 0){
                            var list = response.list_product_allocation;
                            $.each( list, function( key, value ) {
                                if(value["type_product"]){
                                    html+= value["html_render"];
                                }else{
                                    html += '<tr class="list-item-'+key+'">';
                                    html += '<td class="list-image-thumbnail"><img src="'+value["image_thumbnail"]+'"></td>';
                                    html += '<td class="list-product-sku">'+value["sku"]+'</td>';
                                    html += '<td class="list-product-name"><a target="_blank" href="'+value["url_product"]+'">'+value["product_name"]+'</a></td>';
                                    html += '<td class="list-product-allocation-qty">'+value["qty"]+'</td>';
                                    html += '<td class="list-action list-action-'+key+'">';
                                    html += '<div class="list-action-content">';
                                    html += '<input class="input-add-to-cart input-add-my-orders" id="qty_to_cart_'+value["allocation_id"]+'" name="qty_to_cart['+value["allocation_id"]+']" min="'+value["min_sale_qty"]+'" value="'+value["min_sale_qty"]+'">';
                                    html += '<a href="#" class="add-to-cart button" data-index="'+key+'" data-allocation-id="'+value["allocation_id"]+'"><span></span></a>';
                                    html += '</div>';
                                    html += '<span class="label label-message">';
                                    html += '</span>';
                                    html += '</td>';
                                    html +='</tr>';
                                }

                            });

                            $("#my-orders-table tbody").html(html);
                        }else{
                            html += '<tr><td colspan="6"><div><p>'+response.message+'</p></div></td></tr>';
                            $("#my-orders-table tbody").html('');
                            $("#my-orders-table tbody").html(html);
                        }
                    },
                    complete: function(){
                        $('#loading-mask').hide();
                    }
                });
            },
            ajaxCheckQty: function (options) {
                $('.add-to-cart.button').click(function (e) {
                    e.preventDefault();
                    var allocation_id = $(this).attr('data-allocation-id');
                    var qty = $("#qty_to_cart_"+allocation_id).val();
                    $.ajax({
                        url : options.url_add_to_cart,
                        data : {
                            allocation_id: allocation_id,
                            qty: qty
                        },
                        type : 'post',
                        dataType : 'json',
                        showLoader: true,
                        beforeSend : function(){
                            $('#loading-mask').show();
                        },
                        success: function (response) {
                            if(response.message){
                                var sections = ['cart'];
                                customerData.invalidate(sections);
                                customerData.reload(sections, true);
                                $("div[data-placeholder=messages]").html('<div class="allocation-message-success">'+response.notice+'</div>');
                            }else{
                                $("div[data-placeholder=messages]").html('<div class="allocation-message-error">'+response.notice+'</div>');
                            }
                            setTimeout(function(){
                                $("div[data-placeholder=messages]").html('');
                            }, 3000);
                        },
                        complete : function(){
                            $('#loading-mask').hide();
                        }
                    });
                });
            },
        }
    );
    return $.mage.productallocation_manage;
});
