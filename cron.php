<?php
/**
 * 2014-2021 Retargeting BIZ SRL
 **/

$_GET['fc'] = 'module';
$_GET['module'] = 'rtg_tracker';

$_GET['controller'] = 'Static';

if (!isset($_GET['static'])) {
    $_GET['cron'] = true;
}

require_once dirname(__FILE__) . '/../../index.php';

exit(0);