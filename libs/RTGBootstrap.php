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
 * Class RTGBootstrap
 */
class RTGBootstrap
{
    /**
     * @var string
     */
    private static $version = 'v1.6';

    /**
     * Initialisation
     */
    public static function run()
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            self::$version = 'v1.7';
        }

        self::loadCommonLibs();
        self::loadDynamicLibs();
    }

    /**
     * Load common libraries
     */
    public static function loadCommonLibs()
    {
        // Composer vendors
        require_once RTG_TRACKER_DIR . '/vendor/autoload.php';

        // Interfaces
        require_once RTG_TRACKER_DIR . '/libs/common/interfaces/RTGMediaHelperInterface.php';

        // Helpers
        require_once RTG_TRACKER_DIR . '/libs/common/helpers/RTGConfigHelper.php';
        require_once RTG_TRACKER_DIR . '/libs/common/helpers/RTGContextHelper.php';
        require_once RTG_TRACKER_DIR . '/libs/common/helpers/RTGLinkHelper.php';
        require_once RTG_TRACKER_DIR . '/libs/common/helpers/RTGRandomStringGenerator.php';

        // Models
        require_once RTG_TRACKER_DIR . '/libs/common/models/RTGCategoryModel.php';
        require_once RTG_TRACKER_DIR . '/libs/common/models/RTGCustomerModel.php';
        require_once RTG_TRACKER_DIR . '/libs/common/models/RTGManufacturerModel.php';
        require_once RTG_TRACKER_DIR . '/libs/common/models/RTGOrderModel.php';
        require_once RTG_TRACKER_DIR . '/libs/common/models/RTGProductModel.php';
    }

    /**
     * Load libraries by Prestashop version
     */
    public static function loadDynamicLibs()
    {
        // Helpers
        require_once RTG_TRACKER_DIR . '/libs/' . self::$version . '/helpers/RTGMediaHelper.php';
    }
}

/**
 * Init bootstrapper
 */
RTGBootstrap::run();
