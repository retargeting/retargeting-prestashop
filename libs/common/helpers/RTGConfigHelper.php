<?php
/**
 * 2014-2023 Retargeting BIZ SRL.
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

/**
 * Class RTGConfigHelper.
 */
class RTGConfigHelper
{
    const MODULE_NAME = 'rtg_tracker';

    /**
     * Module version.
     */
    public const MODULE_VERSION = '1.0.0';

    /**
     * Module key.
     */
    public const MODULE_KEY = '77bc1af5937025631c4c8009a56191be';

    /**
     * Minimum version of Prestashop.
     */
    public const MINIMUM_VERSION = '1.6.1.04';

    /**
     * Maximum version of Prestashop.
     */
    public const MAXIMUM_VERSION = _PS_VERSION_;

    /**
     * Enable/disable debugging.
     */
    public const ENABLE_DEBUG = _PS_MODE_DEV_;

    /**
     * @var array
     */
    private static $params = [
        'trackingKey' => [
            'id' => 'rtg_tracking_key',
            'json' => false,
        ],
        'restKey' => [
            'id' => 'rtg_rest_key',
            'json' => false,
        ],
        'cartBtnId' => [
            'id' => 'rtg_cart_btn_id',
            'json' => false,
        ],
        'priceLabelId' => [
            'id' => 'rtg_price_label_id',
            'json' => false,
        ],
        'cartUrl' => [
            'id' => 'rtg_cart_url',
            'json' => false,
        ],
        'helpPages' => [
            'id' => 'rtg_help_pages',
            'json' => true,
            'serialized' => false,
        ],
        'productsFeed' => [
            'id' => 'rtg_products_feed',
            'json' => false,
        ],
        'pushNotification' => [
            'id' => 'rtg_push_notification',
            'json' => false,
        ],
        'stockStatus' => [
            'id' => 'rtg_stockStatus',
            'json' => false,
        ],
        'customersFeed' => [
            'id' => 'rtg_customers_feed',
            'json' => false,
        ],
        'defaultLanguage' => [
            'id' => 'rtg_default_language',
            'json' => false,
        ],
        'defaultCurrency' => [
            'id' => 'rtg_default_currency',
            'json' => false,
        ],
        'facebook' => [
            'id' => 'rtg_facebook_key',
            'json' => false,
        ],
        'google' => [
            'id' => 'rtg_google_key',
            'json' => false,
        ],
        'rec_status' => [
            'id' => 'rtg_rec_status',
            'json' => false,
        ],
        'home_page' => [
            'id' => 'rtg_home_page',
            'json' => true,
            'serialized' => true,
        ],
        'category_page' => [
            'id' => 'rtg_category_page',
            'json' => true,
            'serialized' => true,
        ],
        'product_page' => [
            'id' => 'rtg_product_page',
            'json' => true,
            'serialized' => true,
        ],
        'shopping_cart' => [
            'id' => 'rtg_shopping_cart',
            'json' => true,
            'serialized' => true,
        ],
        'thank_you_page' => [
            'id' => 'rtg_thank_you_page',
            'json' => true,
            'serialized' => true,
        ],
        'search_page' => [
            'id' => 'rtg_search_page',
            'json' => true,
            'serialized' => true,
        ],
        'page_404' => [
            'id' => 'rtg_page_404',
            'json' => true,
            'serialized' => true,
        ],
    ];

    /**
     * @var array
     */
    private static $hooks = [
        'displayHeader',
        'displayFooter',
        'displayBeforeBodyClosingTag',
        // 'displayFooterAfter'
        // ,'actionProductUpdate'
    ];

    /**
     * @return bool
     */
    public static function install()
    {
        $response = true;

        foreach (self::$params as $param) {
            if (!Configuration::updateValue($param['id'], '')) {
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

        foreach (self::$params as $param) {
            if (!Configuration::deleteByName($param['id'])) {
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
     * @param mixed $paramKey
     *
     * @return mixed|null
     */
    public static function getParamId($paramKey)
    {
        return !empty(self::$params[$paramKey]) ? self::$params[$paramKey]['id'] : null;
    }

    public static function getSerialized($value)
    {
        return base64_encode(is_array($value) ? serialize($value) : [$value]);
    }

    public static function getUnserialized($value)
    {
        return unserialize(base64_decode($value));
    }

    /**
     * @param bool $mapById
     * @param bool $forInputs
     * @param mixed $paramKey
     *
     * @return array|mixed|string
     */
    public static function getParamValue($paramKey, $mapById = false, $forInputs = false)
    {
        $getParamVal = function ($paramKey) {
            $paramVal = Configuration::get(self::$params[$paramKey]['id']);

            if (self::$params[$paramKey]['json']) {
                $paramVal = self::$params[$paramKey]['serialized'] ?
                    self::getUnserialized($paramVal) : json_decode($paramVal, true);

                if (!is_array($paramVal)) {
                    $paramVal = [];
                }
            }

            return $paramVal;
        };

        if (is_array($paramKey)) {
            $paramValues = [];

            foreach ($paramKey as $pk) {
                $pKey = ($mapById && !empty(self::$params[$pk]['id'])) ? self::$params[$pk]['id'] : $pk;

                if ($forInputs && !empty(self::$params[$pk]['json'])) {
                    $pKey .= '[]';
                }

                $paramValues[$pKey] = $getParamVal($pk);
            }

            return $paramValues;
        }

        return $getParamVal($paramKey);
    }

    /**
     * @param array|string $paramKey
     * @param mixed $paramVal
     *
     * @return bool
     */
    public static function setParamValue($paramKey, $paramVal = null)
    {
        if (!is_array($paramKey)) {
            $paramKey = [
                $paramKey => $paramVal,
            ];
        }

        foreach ($paramKey as $key => $val) {
            if (isset(self::$params[$key])) {
                if (self::$params[$key]['json']) {
                    if (!is_array($val)) {
                        $val = [$val];
                    }

                    $val = self::$params[$key]['serialized'] ?
                        self::getSerialized($val) : json_encode($val);
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

        foreach (self::$params as $paramKey => $param) {
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
