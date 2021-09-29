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
 * @copyright 2014-2021 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

defined('_PS_VERSION_') or exit('No direct script access allowed');

if ((basename(__FILE__) === 'rtg_tracker.php')) {
    define('RTG_TRACKER_DIR', dirname(__FILE__));

    require_once(RTG_TRACKER_DIR . "/libs/RTGBootstrap.php");
}

/**
 * Class Rtg_tracker
 */
class Rtg_tracker extends \Module
{
    /**
     * Ra_Tracker constructor.
     */
    public function __construct()
    {
        $this->name = 'rtg_tracker';
        $this->tab = 'analytics_stats';
        $this->version = "1.0.3";
        $this->author = 'Retargeting BIZ';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '77bc1af5937025631c4c8009a56191be';
        $this->ps_versions_compliancy = [
            'min' => '1.6.1.04',
            'max' => _PS_VERSION_
        ];

        parent::__construct();

        $this->displayName      = $this->l(
            'Retargeting Tracker'
        );
        $this->description      = $this->l(
            'Retargeting is a marketing automation tool that boosts the conversion rate and sales of your online store.'
        );
        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall Retargeting Tracker? You will lose all the data related to this module.'
        );

        if (!RTGConfigHelper::isTrackingApiKeyProvided()) {
            $this->warning = $this->l('No Tracking API Key provided!');
        } else {
            RTGContextHelper::getJSBuilder()->setTrackingApiKey(RTGConfigHelper::getParamValue('trackingKey'));
            RTGContextHelper::getJSBuilder()->setRestApiKey(RTGConfigHelper::getParamValue('restKey'));
        }
    }

    /**
     * @return bool
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

            $output  = '<script type="text/javascript">';
            $output .= RTGContextHelper::getJSBuilder()->getTrackingCode();
            $output .= '</script>';
            $output .= $this->getNecessaryJS();

            return $output;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function hookDisplayFooter()
    {
        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            return RTGContextHelper::getRecommendationEngine()->generateTags();
        }

        return null;
    }

    // public function hookActionProductUpdate()
    // {
    //     $productId = Tools::getValue('id_product');

    //     if (!empty($productId) && RTGContextHelper::getJSBuilder()->hasRestApiKey()) {
    //         $RTGProduct = new RTGProductModel($productId);

    //         $stockManagement = new \RetargetingSDK\Api\StockManagement();
    //         $stockManagement->setProductId($RTGProduct->getId());
    //         $stockManagement->setName($RTGProduct->getName());
    //         $stockManagement->setImage($RTGProduct->getImg());
    //         $stockManagement->setPrice($RTGProduct->getPrice());
    //         $stockManagement->setPromo($RTGProduct->getPromo());
    //         $stockManagement->setStock($RTGProduct->getInventory()['stock']);
    //         $stockManagement->setUrl($RTGProduct->getUrl());

    //         $stockManagement->updateStock(
    //             RTGContextHelper::getJSBuilder()->getRestApiKey(),
    //             $stockManagement->prepareStockInfo()
    //         );
    //     } else {
    //         throw new \RetargetingSDK\Exceptions\RTGException('Missing product id from data!');
    //     }
    // }

    /**
     * @return string|null
     */
    protected function getNecessaryJS()
    {
        $output = null;

        if (RTGContextHelper::getJSBuilder()->hasTrackingApiKey()) {
            $controllersMap = [
                'index'             => [],
                'category'          => [ 'category' ],
                'product'           => [ 'product' ],
                'manufacturer'      => [ 'manufacturer' ],
                'cart'              => [],
                'orderconfirmation' => [],
                'cms'               => [ 'cms' ],
                'search'            => [ 'search_string' ],
                'pagenotfound'      => [],
                'myaccount'         => [],
                'order'             => []
            ];

            if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                $controllersMap['search']                             = [ 'search_query' ];
                $controllersMap['order']                              = 'cart';
                $controllersMap['orderopc']                           = 'cart';
                $controllersMap['module-supercheckout-supercheckout'] = 'cart';
            }

            $controller = Tools::getValue('controller');

            if (Tools::getIsset($controllersMap[$controller])) {
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

                    if ($controller != 'cart' && !empty($cartUrl = RTGConfigHelper::getParamValue('cartUrl'))) {
                        $cartUrl    = RTGLinkHelper::getPathAndQuery($cartUrl);
                        $currentUrl = RTGLinkHelper::getPathAndQuery(RTGLinkHelper::getCurrentLink());
                        $foundCart  = $cartUrl == $currentUrl;

                        if ($foundCart) {
                            $this->prepareCartJS(RTGConfigHelper::getParamValue('cartUrl'));
                        }
                    }

                    if (!$foundCart) {
                        call_user_func_array([$this, 'prepare' . Tools::ucfirst($controller) . 'JS'], $fnParams);
                    }

                    $output = RTGContextHelper::getJSBuilder()->generate();
                } catch (\RetargetingSDK\Exceptions\RTGException $exception) {
                    $message  = '[RTG Tracker] ' . $exception->getMessage();
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

    /**
     * @return void
     */
    protected function prepareIndexJS()
    {
        RTGContextHelper::getJSBuilder()->visitHomePage();
        RTGContextHelper::getRecommendationEngine()->markHomePage();
    }

    /**
     * @param $category
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareCategoryJS($category)
    {
        $categoryId = $this->getIdFromData($category);

        if (empty($categoryId)) {
            $categoryId = (int)Tools::getValue('id_category');
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
     * @param $product
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    protected function prepareProductJS($product)
    {
        $productId = $this->getIdFromData($product);

        if (empty($productId)) {
            $productId = (int)Tools::getValue('id_product');
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
     * @param $manufacturer
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
        $orderId  = (int)Tools::getValue('id_order');
        $RTGOrder = new RTGOrderModel($orderId);

        RTGContextHelper::getJSBuilder()->saveOrder($RTGOrder);
        RTGContextHelper::getRecommendationEngine()->markThankYouPage();
    }

    /**
     * @param $page
     * @return void
     */
    protected function prepareCmsJS($page)
    {
        $pageId    = $this->getIdFromData($page);
        $helpPages = RTGConfigHelper::getParamValue('helpPages');

        if (!empty($pageId) && in_array($pageId, $helpPages)) {
            RTGContextHelper::getJSBuilder()->visitHelpPage();
        }
    }

    /**
     * @param $searchTerm
     * @return void
     */
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

    /**
     * @return void
     */
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
                RTGConfigHelper::setParamsValuesFromRequest();

                $response = $this->displayConfirmation($this->l('The settings have been updated.'));
            }
        }

        return $response;
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    private function renderForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Form helper
        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module             = $this;
        $helper->name_controller    = $this->name;
        $helper->token              = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex       = AdminController::$currentIndex . '&configure=' .$this->name;

        // Language
        $helper->default_form_language      = $defaultLang;
        $helper->allow_employee_form_lang   = $defaultLang;

        // Title and toolbar
        $helper->title          = $this->displayName;
        $helper->show_toolbar   = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action  = 'submit' . $this->name;
        $helper->toolbar_btn    = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => implode('&', [
                    AdminController::$currentIndex,
                    'configure=' . $this->name,
                    'save' . $this->name,
                    'token=' . Tools::getAdminTokenLite('AdminModules')
                ]),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
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
                'icon'  => 'icon-cog'
            ],
            'input' => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Tracking API Key'),
                    'name'      => RTGConfigHelper::getParamId('trackingKey'),
                    'required'  => true
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('REST API Key'),
                    'name'      => RTGConfigHelper::getParamId('restKey'),
                    'desc'      => implode(' ', [
                        'Both keys can be found in your',
                        $this->getLinkHTML(
                            'https://retargeting.biz/admin/module/settings/docs-and-api',
                            'Retargeting'
                        ),
                        'account.'
                    ])
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Default language'),
                    'name' => RTGConfigHelper::getParamId('defaultLanguage'),
                    'desc' => $this->l('Select default language.'),
                    'options' => [
                        'query' => RTGContextHelper::getAllLanguages(),
                        'id' => 'id_option',
                        'name' => 'name'],
                    'required'  => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Default currency'),
                    'name' => RTGConfigHelper::getParamId('defaultCurrency'),
                    'desc' => $this->l('Select default currency.'),
                    'options' => [
                        'query' => RTGContextHelper::getAllCurrencies(),
                        'id' => 'id_option',
                        'name' => 'name'],
                    'required'  => true
                ]
            ]
        ];

        // Optional settings
        $fields[]['form'] = [
            'legend' => [
                'title' => $this->l('Optional Settings'),
                'icon'  => 'icon-cogs'
            ],
            'input' => [
                [
                    'type'      => 'text',
                    'label'     => $this->l('Add to cart button ID'),
                    'name'      => RTGConfigHelper::getParamId('cartBtnId'),
                    'desc'      => 'For more info check ' . $this->getLinkHTML(
                        'https://retargeting.biz/plugins/custom/general',
                        'documentation'
                    ),
                    'placeholder' => '#cart-button-id'
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Price label id'),
                    'name'      => RTGConfigHelper::getParamId('priceLabelId'),
                    'desc'      => 'For more info check ' . $this->getLinkHTML(
                        'https://retargeting.biz/plugins/custom/general',
                        'documentation'
                    ),
                    'placeholder' => '#price-label-id'
                ],
                [
                    'type'        => 'text',
                    'label'       => $this->l('Cart page URL'),
                    'name'        => RTGConfigHelper::getParamId('cartUrl'),
                    'placeholder' => 'https://www.example.com/en/custom-cart',
                    'desc'        => $this->l('Only if you have a custom cart page and not default one.')
                ],
                [
                    'type'      => 'select',
                    'label'     => $this->l('Help Pages'),
                    'name'      => RTGConfigHelper::getParamId('helpPages'),
                    'desc'      => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
                    'multiple'  => true,
                    'class'     => 'chosen',
                    'options'   => [
                        'query' => CMS::listCMS(),
                        'id'    => 'id_cms',
                        'name'  => 'meta_title'
                    ]
                ],
                [
                    'type'      => 'switch',
                    'label'     => $this->l('Products Feed'),
                    'desc'      => implode(' ', [
                        '<b>',
                        $this->l('URL'),
                        '</b> ',
                        $this->getLinkHTML(RTGLinkHelper::getModuleLink('ProductsFeed')),
                        '<br /><b>',
                        $this->l('URL Cron Feed'),
                        '</b> ',
                        $this->getLinkHTML(RTGLinkHelper::getModuleLink('Static')),
                        '<br /><br /><b>',
                        $this->l('Add this to your CronJobs'),
                        '</b><br />',
                        '<code>0 */3 * * * /usr/bin/php '. _PS_MODULE_DIR_ .'modules/rtg_tracker/cron.php >/dev/null 2>&1</code>'
                    ]
                ),
                    'name'      => RTGConfigHelper::getParamId('productsFeed'),
                    'is_bool'   => true,
                    'required'  => false,
                    'values'    => [
                        [
                            'id' => RTGConfigHelper::getParamId('productsFeed') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('productsFeed') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ]
                ],
                [
                    'type'      => 'switch',
                    'label'     => $this->l('Customers Feed'),
                    'desc'      => implode('', [
                        '<b>',
                        $this->l('URL'),
                        '</b> ',
                        $this->getLinkHTML(RTGLinkHelper::getModuleLink('CustomersFeed'))
                    ]),
                    'name'      => RTGConfigHelper::getParamId('customersFeed'),
                    'is_bool'   => true,
                    'required'  => false,
                    'values'    => [
                        [
                            'id' => RTGConfigHelper::getParamId('customersFeed') . '_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => RTGConfigHelper::getParamId('customersFeed') . '_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ]
                ],
            ],
            'submit' => [
                'name'  => 'raSubmitForm',
                'title' => $this->l('Save')
            ]
        ];

        return $fields;
    }

    /**
     * @param $data
     * @param string $idKey
     * @return mixed|null
     */
    private function getIdFromData($data, $idKey = 'id')
    {
        if (is_object($data) && !empty($data->{$idKey})) {
            return $data->{$idKey};
        } elseif (is_array($data) && !empty($data[$idKey])) {
            return $data[$idKey];
        }

        return null;
    }

    /**
     * @param $url
     * @param null $title
     * @return string
     */
    private function getLinkHTML($url, $title = null)
    {
        $link  = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">';
        $link .= !empty($title) ? $title : $url;
        $link .= '</a>';

        return $link;
    }
}