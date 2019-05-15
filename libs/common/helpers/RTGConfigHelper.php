<?php
/**
 * 2014-2019 Retargeting BIZ SRL
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
 * @copyright 2014-2019 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class RTGConfigHelper
 */
class RTGConfigHelper
{
    const MODULE_NAME = 'rtg_tracker';

    /**
     * Module version
     */
    const MODULE_VERSION = '1.0.0';

    /**
     * Minimum version of Prestashop
     */
    const MINIMUM_VERSION = '1.6.1.11';

    /**
     * Maximum version of Prestashop
     */
    const MAXIMUM_VERSION = _PS_VERSION_;

    /**
     * Enable/disable debugging
     */
    const ENABLE_DEBUG = true;

    /**
     * @var array
     */
    private static $params = [
        'trackingKey'   => [
            'id'    => 'rtg_tracking_key',
            'json'  => false
        ],
        'restKey'       => [
            'id'    => 'rtg_rest_key',
            'json'  => false,
        ],
        'cartBtnId'     => [
            'id'    => 'rtg_cart_btn_id',
            'json'  => false,
        ],
        'priceLabelId'  => [
            'id'    => 'rtg_price_label_id',
            'json'  => false,
        ],
        'helpPages'     => [
            'id'    => 'rtg_help_pages',
            'json'  => true
        ],
        'productsFeed'  => [
            'id'    => 'rtg_products_feed',
            'json'  => false
        ],
        'customersFeed' => [
            'id'    => 'rtg_customers_feed',
            'json'  => false
        ]
    ];

    /**
     * @var array
     */
    private static $hooks = [
        'displayHeader',
        'displayFooter'
    ];

    /**
     * @return bool
     */
    public static function install()
    {
        $response = true;

        foreach (self::$params AS $param)
        {
            if (!Configuration::updateValue($param['id'], ''))
            {
                $response = false;

                break;
            }
        }

        return $response;
    }

    /**
     * @return bool
     */
    public static function uninstall()
    {
        $response = true;

        foreach (self::$params AS $param)
        {
            if (!Configuration::deleteByName($param['id']))
            {
                $response = false;

                break;
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    public static function getParams()
    {
        return self::$params;
    }

    /**
     * @param $paramKey
     * @return mixed|null
     */
    public static function getParamId($paramKey)
    {
        return !empty(self::$params[$paramKey]) ? self::$params[$paramKey]['id'] : null;
    }

    /**
     * @param $paramKey
     * @param bool $mapById
     * @param bool $forInputs
     * @return array|mixed|string
     */
    public static function getParamValue($paramKey, $mapById = false, $forInputs = false)
    {
        $getParamVal = function ($paramKey)
        {
            $paramVal = Configuration::get(self::$params[$paramKey]['id']);

            if (self::$params[$paramKey]['json'])
            {
                $paramVal = json_decode($paramVal, true);

                if (!is_array($paramVal))
                {
                    $paramVal = [];
                }
            }

            return $paramVal;
        };

        if (is_array($paramKey))
        {
            $paramValues = [];

            foreach ($paramKey AS $pk)
            {
                $pKey = ($mapById && !empty(self::$params[$pk]['id'])) ? self::$params[$pk]['id'] : $pk;

                if ($forInputs && !empty(self::$params[$pk]['json']))
                {
                    $pKey .= '[]';
                }

                $paramValues[$pKey] = $getParamVal($pk);
            }

            return $paramValues;
        }
        else
        {
            return $getParamVal($paramKey);
        }
    }

    /**
     * @param string|array $paramKey
     * @param mixed $paramVal
     * @return bool
     */
    public static function setParamValue($paramKey, $paramVal = null)
    {
        if (!is_array($paramKey))
        {
            $paramKey = [
                $paramKey => $paramVal
            ];
        }

        foreach ($paramKey AS $key => $val)
        {
            if (isset(self::$params[$key]))
            {
                if (self::$params[$key]['json'])
                {
                    if (!is_array($val))
                    {
                        $val = [ $val ];
                    }

                    $val = json_encode($val);
                }

                Configuration::updateValue(self::$params[$key]['id'], $val);
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function setParamsValuesFromRequest()
    {
        $params = [];

        foreach (self::$params AS $paramKey => $param)
        {
            $params[$paramKey] = Tools::getValue($param['id']);
        }

        return self::setParamValue($params);
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return self::$hooks;
    }

    /**
     * @return bool
     */
    public static function isTrackingApiKeyProvided()
    {
        $trackingKey = self::getParamValue('trackingKey');

        return !empty($trackingKey);
    }
}