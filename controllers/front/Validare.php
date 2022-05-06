<?php
/**
 * 2014-2021 Retargeting BIZ SRL
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2022 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class Rtg_trackerValidareModuleFrontController
 */
class Rtg_trackerValidareModuleFrontController extends ModuleFrontController
{
    private static $params = [
        'rtg_rest_key'
    ];
    private static $list = [
        'google' => ['google-site-verification: ', '.html'],
        'facebook' => ['', '']
    ];

    public function initContent()
    {
        $key = Configuration::get(self::$params[0]);

        $Link  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $Link .= "://{$_SERVER['HTTP_HOST']}";
        $checkKey = Tools::getValue('key');
        $isValid = $checkKey === $key;
        /* code, for, del, */
        if (!$isValid) {
            $message = "Wrong Key RTG Key!";
        } elseif ($isValid && Tools::getValue('code') !== false && Tools::getValue('del')) {
            unlink(_PS_ROOT_DIR_ . '/' . Tools::getValue('code') . '.html');

            $message = 'File Deleted, Please Check ' . $Link . '/' . Tools::getValue('code') . '.html';
        } elseif ($isValid && Tools::getValue('code') !== false) {
            $for = Tools::getValue('for', 'google');
            $do = self::$list[$for];
            $outstream = fopen(_PS_ROOT_DIR_ . '/' . Tools::getValue('code') . '.html', "w+");
            if ($outstream) {
                fwrite($outstream, $do[0] . Tools::getValue('code') . $do[1]);
                fclose($outstream);
            }

            $message = 'All Good, Please Check ' . $Link . '/' . Tools::getValue('code') . '.html';
        }

        echo $message;
            
        exit(0);
    }
}
