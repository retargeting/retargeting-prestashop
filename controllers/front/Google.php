<?php
/**
 * 2014-2021 Retargeting BIZ SRL
 * 
 * @author    Retargeting SRL <info@retargeting.biz>
 * @copyright 2014-2021 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class Rtg_trackerStaticModuleFrontController
 */
class Rtg_trackerGoogleModuleFrontController extends ModuleFrontController
{
    private static $params = [
        'rtg_tracking_key'
    ];

    public function initContent()
    {
        $key = Configuration::get(self::$params[0]);

        $Link  = (Tools::getIsset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $Link .= "://{$_SERVER['HTTP_HOST']}";
        $checkKey = ( Tools::getIsset(Tools::getValue('key')) && !empty($key) && Tools::getValue('key') === $key );

        if( !$checkKey ){
            
            $message = "Wrong Key RTG Key!";
        }elseif( $checkKey && Tools::getIsset(Tools::getValue('code')) && !Tools::getIsset(Tools::getValue('del')) ) {
            
            $outstream = fopen(_PS_ROOT_DIR_ . '/' . Tools::getValue('code') . '.html' , "w+") or die("Unable to open file!");
            fwrite($outstream, 'google-site-verification: ' . Tools::getValue('code') . '.html');
            fclose($outstream);

            $message = 'All Good, Please Check ' . $Link . '/' . Tools::getValue('code') . '.html';
        }elseif ( $checkKey && Tools::getIsset(Tools::getValue('del')) ) {
            
            unlink( _PS_ROOT_DIR_ . '/' . Tools::getValue('code') . '.html' );

            $message = 'File Deleted, Please Check ' . $Link . '/' . Tools::getValue('code') . '.html';
        }

        echo $message ;
            
        exit(0);
    }
}