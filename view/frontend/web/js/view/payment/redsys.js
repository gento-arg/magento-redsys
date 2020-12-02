/**
 * Copyright © Gento <desarrollo@gento.com.ar>, Inc. All rights reserved.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (
    Component,
    rendererList
) {
    'use strict';
    rendererList.push(
        {
            type: 'redsys',
            component: 'Gento_Redsys/js/view/payment/method-renderer/redsys-method'
        }
    );
    return Component.extend({});
}
);