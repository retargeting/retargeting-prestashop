<?php
/**
 * 2014-2021 Retargeting BIZ SRL
 * 
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2021 Retargeting SRL
 */

$_GET['fc'] = 'module';
$_GET['module'] = 'rtg_tracker';

$_GET['controller'] = 'Static';

if (!Tools::getIsset($_GET['static'])) {
    $_GET['cron'] = true;
}

require_once dirname(__FILE__) . '/../../index.php';

exit(0);