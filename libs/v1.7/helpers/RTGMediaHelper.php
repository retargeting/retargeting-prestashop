<?php
/**
 * 2014-2021 Retargeting BIZ SRL
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
 * @copyright 2014-2022 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class RTGMediaHelper
 */
class RTGMediaHelper implements RTGMediaHelperInterface
{
    /**
     * @return mixed|void
     */
    public static function addScripts()
    {
        $scripts = [
            /*[
                'ra_tracker_1',
                RTGContextHelper::getJSBuilder()->getTrackingSrc(),
                [
                    'position'      => 'head',
                    'priority'      => 1001,
                    'inline'        => false,
                    'server'        => 'remote',
                    'attributes'    => 'async'
                ]
            ],*/
            [
                'ra_tracker_2',
                ('modules/' . RTGConfigHelper::MODULE_NAME . '/views/js/v1.7/rtg_tracker.js'),
                [
                    'position'      => 'bottom',
                    'priority'      => 1001,
                    'inline'        => false,
                    'server'        => 'local',
                    'attributes'    => 'async'
                ]
            ]
        ];

        foreach ($scripts as $script) {
            call_user_func_array([ RTGContextHelper::getController(), 'registerJavascript' ], $script);
        }
    }
}
