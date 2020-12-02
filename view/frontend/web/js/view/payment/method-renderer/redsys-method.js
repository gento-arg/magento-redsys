/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */
/*browser:true*/
/*global define*/

define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/place-order',
], function (
    $,
    Component,
    placeOrderAction
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Gento_Redsys/payment/redsys'
        },

        // showCmsBlock: function () {
        //   return window.checkoutConfig.redsysShowCmsBlock;
        // },

        // cmsBlockHtml: function () {
        //   return window.checkoutConfig.redsysBlockHtml;
        // },

        placeOrder: function () {
            var self = this, placeOrder;
            this.isPlaceOrderActionAllowed(false);
            placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

            $.when(placeOrder).fail(function () {
                self.isPlaceOrderActionAllowed(true);
            }).done(this.afterPlaceOrder.bind(this));
        },

        afterPlaceOrder: function () {
            $.ajax({
                // showLoader: true,
                url: window.checkoutConfig.redsysFormDataUrl,
                // data: orderData,
                type: "POST",
                dataType: 'json'
            }).done(function (result) {
                var form = $('<form method="post"></form>');
                form.attr('action', result.url)
                var datos = {
                    'Ds_SignatureVersion': 'HMAC_SHA256_V1',
                    'Ds_MerchantParameters': result.parametros,
                    'Ds_Signature': result.firma,
                };
                for (var key in datos) {
                    var input = $('<input type="hidden" />');
                    input.attr('name', key)
                    input.val(datos[key])
                    form.append(input)
                };

                $('body').append(form);
                form.submit();
            });
        },
    });
}
);