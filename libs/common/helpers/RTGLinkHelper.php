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
 * Class RTGLinkHelper.
 */
class RTGLinkHelper
{
    /**
     * @param string|null $controller
     * @param array $params
     *
     * @return string
     */
    public static function getModuleLink($controller = null, $params = [])
    {
        return Context::getContext()->link->getModuleLink(RTGConfigHelper::MODULE_NAME, $controller, $params, true);
    }

    /**
     * @param mixed $categoryId
     *
     * @return string
     */
    public static function getCategoryLink($categoryId)
    {
        return Context::getContext()->link->getCategoryLink($categoryId);
    }

    /**
     * @param int|Product $product
     * @param null $productAttributeId
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function getProductLink($product, $productAttributeId = null)
    {
        $newLink = Context::getContext()->link->getProductLink(
            $product,
            null,
            null,
            null,
            RTGConfigHelper::getParamValue('defaultLanguage'),
            null,
            $productAttributeId,
            false,
            false,
            true,
        );

        if (!filter_var($newLink, FILTER_VALIDATE_URL)) {
            $newLink = filter_var($newLink, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }

        return $newLink;
    }

    /**
     * @param null $type
     * @param mixed $name
     * @param mixed $ids
     *
     * @return string
     */
    public static function getImageLink($name, $ids, $type = null)
    {
        $newLink = Context::getContext()->link->getImageLink(is_array($name) ? $name[1] : $name, $ids, $type);

        if (!filter_var($newLink, FILTER_VALIDATE_URL)) {
            $newLink = filter_var($newLink, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }

        return $newLink;
    }

    /**
     * @return string
     */
    public static function getCartLink()
    {
        return Context::getContext()->link->getPageLink(
            'cart',
            null,
            RTGContextHelper::getLanguageId(),
            ['action' => 'show'],
            false,
            null,
            true,
        );
    }

    /**
     * @return string
     */
    public static function getCurrentLink()
    {
        $currentLink = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http');
        $currentLink .= "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        return $currentLink;
    }

    /**
     * @param mixed $link
     *
     * @return string
     */
    public static function getPathAndQuery($link)
    {
        $link = parse_url($link);

        if (!empty($link['path'])) {
            $link = $link['path'] . (!empty($link['query']) ? '?' . $link['query'] : '');
        }

        $link = trim($link, '/');
        $link = explode('/', $link);

        if (count($link) > 0 && in_array($link[0], RTGContextHelper::getLanguages('iso_code'))) {
            unset($link[0]);
        }

        return implode('/', $link);
    }
}
