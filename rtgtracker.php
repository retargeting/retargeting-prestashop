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
defined('_PS_VERSION_') or exit('No direct script access allowed');

if ('rtgtracker.php' === basename(__FILE__)) {
    define('RTG_TRACKER_DIR', dirname(__FILE__));

    require_once RTG_TRACKER_DIR . '/libs/RTGBootstrap.php';
}

/**
 * Class Rtgtracker.
 */
class Rtgtracker extends Module
{
    private static $rec_engine = [
        'index' => 'home_page',

        'confirmare-comanda' => 'thank_you_page',
        'order-confirmation' => 'thank_you_page',
        'orderconfirmation' => 'thank_you_page', // Importanta Ordinea checkout_onepage_success

        'cart' => 'shopping_cart',
        'orderopc' => 'shopping_cart',
        'module-supercheckout-supercheckout' => 'shopping_cart',
        'order' => 'shopping_cart',

        'category' => 'category_page',

        'product' => 'product_page',

        'search' => 'search_page',
        'search_query' => 'search_page',
        'search_string' => 'search_page',
        'pagenotfound' => 'page_404',
    ];

    // TODO: RecEngine
    private static $def = [
        'value' => '',
        'selector' => '#main,#columns',
        'place' => 'after',
    ];

    private static $blocks = [
        'block_1' => [
            'title' => 'Block 1',
            'def_rtg' => [
                'value' => '',
                'selector' => '#main,#columns',
                'place' => 'before',
            ],
        ],
        'block_2' => [
            'title' => 'Block 2',
        ],
        'block_3' => [
            'title' => 'Block 3',
        ],
        'block_4' => [
            'title' => 'Block 4',
        ],
    ];

    private static $fields = [
        'home_page' => [
            'title' => 'Home Page',
            'type' => 'rec_engine',
        ],
        'category_page' => [
            'title' => 'Category Page',
            'type' => 'rec_engine',
        ],
        'product_page' => [
            'title' => 'Product Page',
            'type' => 'rec_engine',
        ],
        'shopping_cart' => [
            'title' => 'Shopping Cart',
            'type' => 'rec_engine',
            'def_rtg' => [
                'value' => '',
                'selector' => '#main,#content,#columns',
                'place' => 'after',
            ],
            'child' => [
                'block_1' => [
                    'title' => 'Block 1',
                    'def_rtg' => [
                        'value' => '',
                        'selector' => '#main,#content,#columns',
                        'place' => 'before',
                    ],
                ],
                'block_2' => [
                    'title' => 'Block 2',
                ],
                'block_3' => [
                    'title' => 'Block 3',
                ],
                'block_4' => [
                    'title' => 'Block 4',
                ],
            ],
        ],
        'thank_you_page' => [
            'title' => 'Thank you Page',
            'type' => 'rec_engine',
        ],
        'search_page' => [
            'title' => 'Search Page',
            'type' => 'rec_engine',
        ],
        'page_404' => [
            'title' => 'Page 404',
            'type' => 'rec_engine',
        ],
    ];
    private $pushList = [
        'manifest.json' => '{"name":"{{BASE}}","short_name":"{{BASE}}","start_url":"/","display":' .
            '"standalone","gcm_sender_id":"482941778795"}',
        'OneSignalSDKUpdaterWorker.js' => "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
        'OneSignalSDKWorker.js' => "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');",
    ];
    private $linkBase;
    private static $validList = [
        'google' => ['google-site-verification: ', '.html'],
        'facebook' => ['', ''],
    ];

    /**
     * Ra_Tracker constructor.
     */
    public function __construct()
    {
        // $tab = _PS_VERSION_ > '1.7' ? 'advertising_marketing' : 'analytics_stats';
        // $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->name = 'rtgtracker';
        $this->tab = 'advertising_marketing';
        $this->version = '1.1.7';
        $this->author = 'Retargeting BIZ';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '77bc1af5937025631c4c8009a56191be';

        parent::__construct();

        $this->displayName = 'Retargeting Tracker';
        $this->description = 'Retargeting is a marketing automation tool that boosts the conversion rate and sales of your online store.';
        $this->confirmUninstall = 'Are you sure you want to uninstall Retargeting Tracker? You will lose all the data related to this module.';

        if (!RTGConfigHelper::isTrackingApiKeyProvided()) {
            $this->warning = 'No Tracking API Key provided!';
        } else {
            RTGContextHelper::getJSBuilder()->setTrackingApiKey(RTGConfigHelper::getParamValue('trackingKey'));
            RTGContextHelper::getJSBuilder()->setRestApiKey(RTGConfigHelper::getParamValue('restKey'));
        }
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && RTGConfigHelper::install()
            && $this->registerHook(RTGConfigHelper::getHooks());
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && RTGConfigHelper::uninstall();
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    /**
     * @return string|null
     */
    public function hookDisplayHeader()
    {
        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            RTGContextHelper::getJSBuilder()->setAddToCardId(RTGConfigHelper::getParamValue('cartBtnId'));
            RTGContextHelper::getJSBuilder()->setPriceLabelId(RTGConfigHelper::getParamValue('priceLabelId'));

            RTGMediaHelper::addScripts();
            // Context::getContext()->controller->addJS('<script>console.info("' . Tools::getValue('controller') . '","|RTG")</script>');

            $output = '<script type="text/javascript">';
            $output .= RTGContextHelper::getJSBuilder()->getTrackingCode();
            $output .= '</script>';
            $output .= $this->getNecessaryJS();

            return $output;
        }

        return null;
    }

    private static $footerLoad = true;

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        if (self::$footerLoad) {
            self::$footerLoad = false;
            return '<script type="text/javascript">' . self::getRecEngine(Tools::getValue('controller')) . '</script>';
        }
    }

    /**
     * @return string|null
     */
    public function hookDisplayFooter()
    {
        if (self::$footerLoad && RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            self::$footerLoad = false;
            /* RTGContextHelper::getRecommendationEngine()->generateTags() */
            return '<script type="text/javascript">' . self::getRecEngine(Tools::getValue('controller')) . '</script>';
        }
        return null;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        if (null === $this->linkBase) {
            $this->linkBase = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http');
            $this->linkBase .= "://{$_SERVER['HTTP_HOST']}";
        }

        return $this->linkBase;
    }

    public static function getRecEngine($ActionName = null)
    {
        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey() && (bool) RTGConfigHelper::getParamValue('rec_status')) {
            if (isset(self::$rec_engine[$ActionName])) {
                return '
                var _ra_rec_engine = {};
    
                _ra_rec_engine.init = function () {
                    let list = this.list;
                    for (let key in list) {
                        _ra_rec_engine.insert(list[key].value, list[key].selector, list[key].place);
                    }
                };
    
                _ra_rec_engine.insert = function (code = "", selector = null, place = "before") {
                    if (code !== "" && selector !== null) {
                        let newTag = document.createRange().createContextualFragment(code);
                        let content = document.querySelector(selector);
    
                        content.parentNode.insertBefore(newTag, place === "before" ? content : content.nextSibling);
                    }
                };
                _ra_rec_engine.list = ' . json_encode(RTGConfigHelper::getParamValue(self::$rec_engine[$ActionName])) . ';
                _ra_rec_engine.init();';
            }
        }

        return '';
    }

    /*
    public function hookDisplayFooterAfter()
    {
        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            return RTGContextHelper::getRecommendationEngine()->generateTags();
        }
        return null;
    }
    */

/*
    public function hookActionProductUpdate()
    {
        $productId = Tools::getValue('id_product');

        if (!empty($productId) && RTGContextHelper::getJSBuilder()->hasRestApiKey()) {
            $RTGProduct = new RTGProductModel($productId);

            $stockManagement = new \RetargetingSDK\Api\StockManagement();
            $stockManagement->setProductId($RTGProduct->getId());
            $stockManagement->setName($RTGProduct->getName());
            $stockManagement->setImage($RTGProduct->getImg());
            $stockManagement->setPrice($RTGProduct->getPrice());
            $stockManagement->setPromo($RTGProduct->getPromo());
            $stockManagement->setStock($RTGProduct->getInventory()['stock']);
            $stockManagement->setUrl($RTGProduct->getUrl());

            $stockManagement->updateStock(
                RTGContextHelper::getJSBuilder()->getRestApiKey(),
                $stockManagement->prepareStockInfo()
            );
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Missing product id from data!');
        }
    }
*/
    /**
     * @return string|null
     */
    protected function getNecessaryJS()
    {
        $output = null;

        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            $controllersMap = [
                'index' => [],
                'category' => ['category'],
                'product' => ['product'],
                'manufacturer' => ['manufacturer'],
                'cart' => [],
                'orderconfirmation' => [],

                'order-confirmation' => 'orderconfirmation',
                'confirmare-comanda' => 'orderconfirmation',

                'cms' => ['cms'],
                'search' => ['search_string'],
                'pagenotfound' => [],
                'myaccount' => [],
                'order' => [],
            ];

            if (version_compare(_PS_VERSION_, '1.7.9.0', '<')) {
                $controllersMap['search'] = ['search_query'];
                $controllersMap['order'] = 'cart';
                $controllersMap['orderopc'] = 'cart';
                $controllersMap['module-supercheckout-supercheckout'] = 'cart';
            }

            $controller = Tools::getValue('controller');

            if (isset($controllersMap[$controller])) {
                $fnParams = [];

                if (!empty($controllersMap[$controller])) {
                    if (!is_array($controllersMap[$controller])) {
                        $controller = $controllersMap[$controller];
                    } else {
                        foreach ($controllersMap[$controller] as $fnParam) {
                            $fnParams[] = $this->context->smarty->getTemplateVars($fnParam);
                        }
                    }
                }

                try {
                    $foundCart = false;

                    if ('cart' != $controller && !empty($cartUrl = RTGConfigHelper::getParamValue('cartUrl'))) {
                        $cartUrl = RTGLinkHelper::getPathAndQuery($cartUrl);
                        $currentUrl = RTGLinkHelper::getPathAndQuery(RTGLinkHelper::getCurrentLink());
                        $foundCart = $cartUrl == $currentUrl;

                        if ($foundCart) {
                            $this->prepareCartJS(RTGConfigHelper::getParamValue('cartUrl'));
                        }
                    }

                    if (!$foundCart) {
                        call_user_func_array([$this, 'prepare' . Tools::ucfirst($controller) . 'JS'], $fnParams);
                    }

                    $output = RTGContextHelper::getJSBuilder()->generate();
                } catch (\RetargetingSDK\Exceptions\RTGException $exception) {
                    $message = '[RTG Tracker] ' . $exception->getMessage();
                    $message .= ' in file ' . $exception->getFile();
                    $message .= ' on line ' . $exception->getLine();

                    if (RTGConfigHelper::ENABLE_DEBUG) {
                        PrestaShopLogger::addLog($message);
                    }
                }
            }
        }

        return $output;
    }

    protected function prepareIndexJS()
    {
        RTGContextHelper::getJSBuilder()->visitHomePage();
        RTGContextHelper::getRecommendationEngine()->markHomePage();
    }

    /**
     * @param mixed $category
     *
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareCategoryJS($category)
    {
        $categoryId = $this->getIdFromData($category);

        if (empty($categoryId)) {
            $categoryId = (int) Tools::getValue('id_category');
        }

        if (!empty($categoryId)) {
            $RTGCategory = new RTGCategoryModel($categoryId);

            RTGContextHelper::getJSBuilder()->sendCategory($RTGCategory);
            RTGContextHelper::getRecommendationEngine()->markCategoryPage();
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Missing category id from data!');
        }
    }

    /**
     * @param mixed $product
     *
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareProductJS($product)
    {
        $productId = $this->getIdFromData($product);

        if (empty($productId)) {
            $productId = (int) Tools::getValue('id_product');
        }

        if (!empty($productId)) {
            $RTGProduct = new RTGProductModel($productId);

            RTGContextHelper::getJSBuilder()->sendProduct($RTGProduct);
            RTGContextHelper::getJSBuilder()->likeFacebook($RTGProduct->getId());
            RTGContextHelper::getRecommendationEngine()->markProductPage();
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Missing product id from data!');
        }
    }

    /**
     * @param mixed $manufacturer
     *
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareManufacturerJS($manufacturer)
    {
        $manufacturerId = $this->getIdFromData($manufacturer);

        if (!empty($manufacturerId)) {
            $RTGManufacturer = new RTGManufacturerModel($manufacturerId);

            RTGContextHelper::getJSBuilder()->sendBrand($RTGManufacturer);
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Missing manufacturer id from data!');
        }
    }

    /**
     * @param null $url
     */
    protected function prepareCartJS($url = null)
    {
        $products = RTGContextHelper::getCart()->getProducts();

        if (!empty($products)) {
            $productsIds = [];

            foreach ($products as $product) {
                $productsIds[] = $product['id_product'];
            }

            $RTGCheckout = new \RetargetingSDK\Checkout();
            $RTGCheckout->setProductIds($productsIds);

            RTGContextHelper::getJSBuilder()->checkoutIds($RTGCheckout);
        }

        if (empty($url)) {
            $url = RTGLinkHelper::getCartLink();
        }

        RTGContextHelper::getJSBuilder()->setCartUrl($url);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareOrderconfirmationJs()
    {
        $orderId = (int) Tools::getValue('id_order');

        if (empty($orderId)) {
            $cartId = (int) Tools::getValue('orderId');
            $orderId = Order::getIdByCartId($cartId);
        }

        $RTGOrder = new RTGOrderModel($orderId);

        RTGContextHelper::getJSBuilder()->saveOrder($RTGOrder);
        RTGContextHelper::getRecommendationEngine()->markThankYouPage();
    }

    /**
     * @for PS version 8 or higher
     */
    protected function prepareOrderJs()
      {
        $this->prepareOrderconfirmationJs();
      }

    protected function prepareCmsJS($page)
    {
        $pageId = $this->getIdFromData($page);
        $helpPages = RTGConfigHelper::getParamValue('helpPages');

        if (!empty($pageId) && in_array($pageId, $helpPages)) {
            RTGContextHelper::getJSBuilder()->visitHelpPage();
        }
    }

    protected function prepareSearchJS($searchTerm)
    {
        if (empty($searchTerm)) {
            $searchTerm = Tools::getValue('search_query', null);
        }

        if (!empty($searchTerm)) {
            RTGContextHelper::getJSBuilder()->sendSearchTerm($searchTerm);
        }

        RTGContextHelper::getRecommendationEngine()->markSearchPage();
    }

    protected function preparePagenotfoundJS()
    {
        RTGContextHelper::getJSBuilder()->pageNotFound();
        RTGContextHelper::getRecommendationEngine()->markNotFoundPage();
    }

    /**
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareMyaccountJS()
    {
        $customerId = $this->getIdFromData(Context::getContext()->customer);

        if (!empty($customerId)) {
            $RTGCustomer = new RTGCustomerModel($customerId);

            RTGContextHelper::getJSBuilder()->setEmail($RTGCustomer);
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Missing customer id from data!');
        }
    }

    private function doPush()
    {
        if ($this->isPushEnabled()) {
            foreach ($this->pushList as $k => $v) {
                $outstream = fopen(_PS_ROOT_DIR_ . '/' . $k, 'w+');
                if ($outstream) {
                    if ('manifest.json' === $k) {
                        $v = str_replace('{{BASE}}', $this->getLink(), $v);
                    }
                    fwrite($outstream, $v);
                    fclose($outstream);
                }
            }
        } else {
            foreach ($this->pushList as $k => $v) {
                if (file_exists(_PS_ROOT_DIR_ . '/' . $k)) {
                    unlink(_PS_ROOT_DIR_ . '/' . $k);
                }
            }
        }
    }

    private function updateValid()
    {
        foreach (self::$validList as $key => $val) {
            $ex = Configuration::get(RTGConfigHelper::getParamId($key));
            if (!empty($ex)) {
                unlink(_PS_ROOT_DIR_ . '/' . $ex . '.html');
            }
            $new = Tools::getValue(RTGConfigHelper::getParamId($key));
            if ($new) {
                $outstream = fopen(_PS_ROOT_DIR_ . '/' . $new . '.html', 'w+');
                if ($outstream) {
                    fwrite($outstream, $val[0] . $new . $val[1]);
                    fclose($outstream);
                }
            }
        }
    }

    private function isPushEnabled()
    {
        $paramVal = Configuration::get(RTGConfigHelper::getParamId('pushNotification'));

        return (int) $paramVal > 0;
    }

    /**
     * @return string
     */
    private function postProcess()
    {
        $response = '';

        if (Tools::isSubmit('raSubmitForm')) {
            if (Tools::isEmpty(Tools::getValue(RTGConfigHelper::getParamId('trackingKey')))) {
                $response = $this->displayError($this->l('The field `Tracking API Key` is required!'));
            } else {
                $this->updateValid();
                RTGConfigHelper::setParamsValuesFromRequest();
                $this->doPush();
                $response = $this->displayConfirmation($this->l('The settings have been updated.'));
            }
        }

        return $response;
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    private function renderForm()
    {
        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Form helper
        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => implode('&', [
                    AdminController::$currentIndex,
                    'configure=' . $this->name,
                    'save' . $this->name,
                    'token=' . Tools::getAdminTokenLite('AdminModules'),
                ]),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];

        // Load current values
        $helper->fields_value = RTGConfigHelper::getParamValue(array_keys(RTGConfigHelper::getParams()), true, true);
        // Return the form
        return $helper->generateForm(
            $this->getFormFields()
        );
    }

    /**
     * @return array
     */
    private function getFormFields()
    {
        $fields = [];

        // Required settings
        $fields[]['form'] = [
            'legend' => [
                'title' => $this->l('Required Settings'),
                'icon' => 'icon-cog',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Tracking API Key'),
                    'name' => RTGConfigHelper::getParamId('trackingKey'),
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('REST API Key'),
                    'name' => RTGConfigHelper::getParamId('restKey'),
                    'desc' => implode(' ', [
                        'Both keys can be found in your',
                        $this->getLinkHTML(
                            'https://retargeting.biz/plugins/custom/api-integration/add-subscriber',
                            'Retargeting'
                        ),
                        'account.',
                    ]),
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Default language'),
                    'name' => RTGConfigHelper::getParamId('defaultLanguage'),
                    'desc' => $this->l('Select default language.'),
                    'options' => [
                        'query' => RTGContextHelper::getAllLanguages(),
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Default currency'),
                    'name' => RTGConfigHelper::getParamId('defaultCurrency'),
                    'desc' => $this->l('Select default currency.'),
                    'options' => [
                        'query' => RTGContextHelper::getAllCurrencies(),
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                    'required' => true,
                ],
            ],
            'submit' => [
                'name' => 'raSubmitForm',
                'title' => $this->l('Save'),
            ],
        ];

        // Optional settings
        $fields[]['form'] = [
            'legend' => [
                'title' => $this->l('Optional Settings'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Add to cart button ID'),
                    'name' => RTGConfigHelper::getParamId('cartBtnId'),
                    'desc' => 'For more info check ' . $this->getLinkHTML(
                        'https://retargeting.biz/plugins/custom',
                        'documentation'
                    ),
                    'placeholder' => '#cart-button-id',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Price label id'),
                    'name' => RTGConfigHelper::getParamId('priceLabelId'),
                    'desc' => 'For more info check ' . $this->getLinkHTML(
                        'https://retargeting.biz/plugins/custom',
                        'documentation'
                    ),
                    'placeholder' => '#price-label-id',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Cart page URL'),
                    'name' => RTGConfigHelper::getParamId('cartUrl'),
                    'placeholder' => 'https://www.example.com/en/custom-cart',
                    'desc' => $this->l('Only if you have a custom cart page and not default one.'),
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Help Pages'),
                    'name' => RTGConfigHelper::getParamId('helpPages'),
                    'desc' => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
                    'multiple' => true,
                    'class' => 'chosen',
                    'options' => [
                        'query' => CMS::listCMS(),
                        'id' => 'id_cms',
                        'name' => 'meta_title',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Products Feed'),
                    'desc' => implode(' ', [
                        '<b>',
                        $this->l('URL'),
                        '</b> ',
                        $this->getLinkHTML(RTGLinkHelper::getModuleLink('ProductsFeed')),
                        '<br /><b>',
                        $this->l('URL Cron Feed'),
                        '</b> ',
                        $this->getLinkHTML(
                            Tools::substr(
                                RTGLinkHelper::getModuleLink('ProductsFeed', ['static' => '']),
                                0,
                                -1
                            )
                        ),
                        '<br /><br /><b>',
                        $this->l('Add this to your CronJobs'),
                        '</b><br />',
                        '<code>0 */3 * * * /usr/bin/php ' . _PS_MODULE_DIR_
                        . 'rtgtracker/cron.php >/dev/null 2>&1</code>',
                    ]),
                    'name' => RTGConfigHelper::getParamId('productsFeed'),
                    'is_bool' => true,
                    'required' => false,
                    'values' => [
                        [
                            'id' => RTGConfigHelper::getParamId('productsFeed') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('productsFeed') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ], [
                    'type' => 'switch',
                    'label' => $this->l('Default Stock Status'),
                    'desc' => implode('', [
                        '<b>',
                        'Default stock Status if is negative quantity like "-1"',
                        '</b> ',
                    ]),
                    'name' => RTGConfigHelper::getParamId('stockStatus'),
                    'is_bool' => true,
                    'required' => false,
                    'values' => [
                        [
                            'id' => RTGConfigHelper::getParamId('stockStatus') . '_on',
                            'value' => 1,
                            'label' => $this->l('In Stock'),
                        ], [
                            'id' => RTGConfigHelper::getParamId('stockStatus') . '_off',
                            'value' => 0,
                            'label' => $this->l('Out of Stock'),
                        ],
                    ],
                ], [
                    'type' => 'switch',
                    'label' => $this->l('Customers Feed'),
                    'desc' => implode('', [
                        '<b>',
                        $this->l('URL'),
                        '</b> ',
                        $this->getLinkHTML(RTGLinkHelper::getModuleLink('CustomersFeed')),
                    ]),
                    'name' => RTGConfigHelper::getParamId('customersFeed'),
                    'is_bool' => true,
                    'required' => false,
                    'values' => [
                        [
                            'id' => RTGConfigHelper::getParamId('customersFeed') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('customersFeed') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ], [
                    'type' => 'switch',
                    'label' => $this->l('Push Notification'),
                    'desc' => '<b>' . $this->l('This will add Push Notifications Files in Root') . '</b> ',
                    'name' => RTGConfigHelper::getParamId('pushNotification'),
                    'is_bool' => true,
                    'required' => false,
                    'values' => [
                        [
                            'id' => RTGConfigHelper::getParamId('pushNotification') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('pushNotification') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ], [
                    'type' => 'text',
                    'label' => $this->l('Facebook Domain Verification'),
                    'name' => RTGConfigHelper::getParamId('facebook'),
                    'placeholder' => 'Key',
                    'desc' => $this->l('Domain Verification.'),
                ], [
                    'type' => 'text',
                    'label' => $this->l('Google Domain Verification'),
                    'name' => RTGConfigHelper::getParamId('google'),
                    'placeholder' => 'Key',
                    'desc' => $this->l('Domain Verification.'),
                ],
            ],
            'submit' => [
                'name' => 'raSubmitForm',
                'title' => $this->l('Save'),
            ],
        ];

        $recEngine = [
            'legend' => [
                'title' => $this->l('Recommendation Engine'),
                'icon' => 'icon-cogs',
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Status'),
                    'name' => RTGConfigHelper::getParamId('rec_status'),
                    'is_bool' => true,
                    'required' => false,
                    'values' => [
                        [
                            'id' => RTGConfigHelper::getParamId('rec_status') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('rec_status') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'name' => 'raSubmitForm',
                'title' => $this->l('Save'),
            ],
        ];

        foreach (self::$fields as $row => $selected) {
            $key = RTGConfigHelper::getParamId($row);
            $html_content = '';

            $value = RTGConfigHelper::getParamValue($row);

            foreach ((isset($selected['child']) ? $selected['child'] : self::$blocks) as $k => $v) {
                if (empty($value[$k]['value']) && empty($value[$k]['selector'])) {
                    $def = isset($v['def_rtg']) ?
                        $v['def_rtg'] : (isset($selected['def_rtg']) ? $selected['def_rtg'] : null);

                    $value[$k] = null !== $def ? $def : self::$def;
                }
                $html_content .= '<label for="addon_' . $row . '_' . $k . '">' . $v['title'] . '</label>';

                $html_content .= '<textarea style="height: 75px;" ' .
                ' id="addon_' . $row . '_' . $k . '" name="' . $key . '[' . $k . '][value]" spellcheck="false">' . $value[$k]['value'] . '</textarea>' . "\n";

                $html_content .= '<p><span><strong>' .
                    '<a href="javascript:void(0);" onclick="document.querySelectorAll(\'#' .
                    $row . '_advace\').forEach((e)=>{e.style.display=e.style.display===\'none\'?\'block\':\'none\';});">' .
                    'Show/Hide Advance</a></strong></span></p>';

                $html_content .= '<span id="' . $row . '_advace" style="display:none" >' .
                        '<input style="width:79%;display:inline;" class="form-control"' .
                        ' id="' . $row . '_' . $k . '_adv_sel" type="text" name="' . $key . '[' . $k . '][selector]" ' .
                        'value="' . $value[$k]['selector'] . '" />' . "\n";

                $html_content .= '<select style="width:20.5%;display:inline;" id="' .
                    $row . '_' . $k . '_adv_opt" name="' . $key . '[' . $k . '][place]">' . "\n";

                foreach (['before', 'after'] as $v) {
                    $html_content .= '<option value="' . $v . '"' . ($value[$k]['place'] === $v ? ' selected="selected"' : '');
                    $html_content .= '>' . $v . '</option>' . "\n";
                }

                $html_content .= '</select></span><br />' . "\n";
            }
            $recEngine['input'][] = [
                'type' => 'html',
                'label' => $this->l($selected['title']),
                'name' => $row,
                'html_content' => $html_content,
            ];
        }

        $fields[]['form'] = $recEngine;

        return $fields;
    }

    /**
     * @param string $idKey
     * @param mixed $data
     *
     * @return mixed|null
     */
    private function getIdFromData($data, $idKey = 'id')
    {
        if (is_object($data) && !empty($data->{$idKey})) {
            return $data->{$idKey};
        }
        if (is_array($data) && !empty($data[$idKey])) {
            return $data[$idKey];
        }

        return null;
    }

    /**
     * @param null $title
     * @param mixed $url
     *
     * @return string
     */
    private function getLinkHTML($url, $title = null)
    {
        $link = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">';
        $link .= !empty($title) ? $title : $url;
        $link .= '</a>';

        return $link;
    }
}
