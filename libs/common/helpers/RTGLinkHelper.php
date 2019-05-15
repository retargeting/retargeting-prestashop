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
 * Class RTGLinkHelper
 */
class RTGLinkHelper
{
    /**
     * @param null|string $controller
     * @param array $params
     * @return string
     */
    public static function getModuleLink($controller = null, $params = [])
    {
        return Context::getContext()->link->getModuleLink(RTGConfigHelper::MODULE_NAME, $controller, $params, true);
    }

    /**
     * @param $categoryId
     * @return string
     */
    public static function getCategoryLink($categoryId)
    {
        return Context::getContext()->link->getCategoryLink($categoryId);
    }

    /**
     * @param Product|int $product
     * @param null $productAttributeId
     * @return string
     * @throws PrestaShopException
     */
    public static function getProductLink($product, $productAttributeId = null)
    {
        return Context::getContext()->link->getProductLink(
            $product,
            null,
            null,
            null,
            RTGContextHelper::getLanguageId(),
            null,
            $productAttributeId,
            false,
            false,
            true
        );
    }

    /**
     * @param $name
     * @param $ids
     * @param null $type
     * @return string
     */
    public static function getImageLink($name, $ids, $type = null)
    {
        return Context::getContext()->link->getImageLink($name, $ids, $type);
    }

    /**
     * @return string
     */
    public static function getCartLink()
    {
        return Context::getContext()->link->getPageLink('cart', null, RTGContextHelper::getLanguageId(), [ 'action' => 'show' ], false, null, true);
    }
}