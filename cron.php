<?php
/**
 * 2014-2021 Retargeting BIZ SRL
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2022 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$_GET['fc'] = 'module';
$_GET['module'] = 'rtg_tracker';

$_GET['controller'] = 'ProductsFeed';

if (!Tools::getIsset('static')) {
    $_GET['cron'] = true;
} else {
    $_GET['static'] = true;
}

require_once dirname(__FILE__) . '/../../index.php';

exit();
