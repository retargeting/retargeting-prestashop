/**
 * 2014-2023 Retargeting BIZ SRL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@retargeting.biz so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2023 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
(function ()
{
    'use strict';

    $( document ).ajaxSuccess(function( event, xhr, settings )
    {
        var dataOb = {};

        if (settings.data instanceof FormData)
        {
            for(var pair of settings.data.entries())
            {
                dataOb[pair[0]] = pair[1];
            }
        }
        else if(typeof settings.data === 'string')
        {
            settings.data.replace(/([^=&]+)=([^&]*)/g, function(m, key, value)
            {
                dataOb[decodeURIComponent(key)] = decodeURIComponent(value);
            });
        }

        if(dataOb.hasOwnProperty('controller') && dataOb.controller === 'cart' && dataOb.hasOwnProperty('id_product'))
        {
            if(typeof _ra === "undefined")
            {
                _ra = {};
            }

            if(dataOb.hasOwnProperty('add'))
            {
                if([1, '1', 'true'].indexOf(dataOb.add) >= 0)
                {
                    if(dataOb.hasOwnProperty('op') && dataOb.op === 'down')
                    {
                        _ra.removeFromCartInfo = {
                            "product_id": dataOb.id_product,
                            "quantity"  : dataOb.hasOwnProperty('qty') ? dataOb.qty : '1',
                            "variation" : false
                        };

                        if (_ra.ready !== undefined)
                        {
                            _ra.removeFromCart(
                                _ra.removeFromCartInfo.product_id,
                                _ra.removeFromCartInfo.quantity,
                                _ra.removeFromCartInfo.variation
                            );
                        }
                    }
                    else
                    {
                        _ra.addToCartInfo = {
                            "product_id": dataOb.id_product,
                            "quantity"  : dataOb.hasOwnProperty('qty') ? dataOb.qty : '1',
                            "variation" : false
                        };

                        if (_ra.ready !== undefined)
                        {
                            _ra.addToCart(
                                _ra.addToCartInfo.product_id,
                                _ra.addToCartInfo.quantity,
                                _ra.addToCartInfo.variation
                            );
                        }
                    }
                }
            }
            else if(dataOb.hasOwnProperty('delete'))
            {
                _ra.removeFromCartInfo = {
                    "product_id": dataOb.id_product,
                    "quantity"  : dataOb.hasOwnProperty('qty') ? dataOb.qty : '1',
                    "variation" : false
                };

                if (_ra.ready !== undefined)
                {
                    _ra.removeFromCart(
                        _ra.removeFromCartInfo.product_id,
                        _ra.removeFromCartInfo.quantity,
                        _ra.removeFromCartInfo.variation
                    );
                }
            }
        }
    });
})();