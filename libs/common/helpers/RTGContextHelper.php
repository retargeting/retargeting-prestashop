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
 * Class RTGContextHelper
 */
class RTGContextHelper
{
    /**
     * @var null|\RetargetingSDK\Javascript\Builder;
     */
    private static $JSBuilderInstance;

    /**
     * @var null|\RetargetingSDK\RecommendationEngine;
     */
    private static $RecommendationEngineInstance;

    /**
     * @return AdminController|FrontController
     */
    public static function getController()
    {
        return Context::getContext()->controller;
    }

    /**
     * @return Language
     */
    public static function getLanguage()
    {
        return Context::getContext()->language;
    }

    /**
     * @param null $param
     * @return array
     */
    public static function getLanguages($param = null)
    {
        $languages = Language::getLanguages(self::getShop('id'));

        if (!empty($param)) {
            return !empty($languages) ? array_column($languages, $param) : [];
        }

        return $languages;
    }

    /**
     * @return int|null
     */
    public static function getLanguageId()
    {
        return self::getLanguage() ? (int)self::getLanguage()->id : null;
    }

    /**
     * @param null $param
     * @return Shop|null
     */
    public static function getShop($param = null)
    {
        $shop = Context::getContext()->shop;

        if (!empty($param)) {
            return $shop && !empty($shop->{$param}) ? $shop->{$param} : null;
        }

        return $shop;
    }

    /**
     * @return Cart
     */
    public static function getCart()
    {
        return Context::getContext()->cart;
    }

    /**
     * @return \RetargetingSDK\Javascript\Builder|null
     */
    public static function getJSBuilder()
    {
        if (!self::$JSBuilderInstance instanceof \RetargetingSDK\Javascript\Builder) {
            self::$JSBuilderInstance = new \RetargetingSDK\Javascript\Builder();
        }

        return self::$JSBuilderInstance;
    }

    /**
     * @return \RetargetingSDK\RecommendationEngine|null
     */
    public static function getRecommendationEngine()
    {
        if (!self::$RecommendationEngineInstance instanceof \RetargetingSDK\RecommendationEngine) {
            self::$RecommendationEngineInstance = new \RetargetingSDK\RecommendationEngine();
        }

        return self::$RecommendationEngineInstance;
    }

    /**
     * @return array
     */
    public static function getAllLanguages() {

        $languages = Language::getLanguages();
        $formatedLanguages = [];
        foreach ($languages as $key => $language) {
            $formatedLanguages[] = ['id_option' => $language['id_lang'], 'name' => $language['name']];
        }

        return $formatedLanguages;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getAllCurrencies() {


        $currencies = CurrencyCore::getCurrencies();
        $formatedCurrenies = [];
        foreach ($currencies as $key => $currency) {
            $formatedCurrenies[] = ['id_option' => $currency['id_currency'], 'name' => $currency['iso_code']];
        }

        return $formatedCurrenies;
    }

    /**
     * @param $price
     * @param $currencyId
     */
    public static function convertCurrency($price) {
        GLOBAL $currency;

        $convertedPrice = $price;
        $defaultCurrency = RTGConfigHelper::getParamValue('defaultCurrency');

        if ($currency->id != $defaultCurrency) {
            $defaultCurrency = CurrencyCore::getCurrencyInstance($defaultCurrency);
            $convertedPrice = Tools::convertPriceFull($price, $currency, $defaultCurrency);
        }

        return round($convertedPrice, 2);

    }
}
