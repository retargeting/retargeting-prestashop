<?php

defined('_PS_VERSION_') OR exit('No direct script access allowed');

/**
 * 2014-2018 Retargeting BIZ SRL
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
 * @copyright 2014-2018 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once(__DIR__ . '/vendor/autoload.php');

/**
 * Class Ra_Tracker
 */
class Ra_Tracker extends Module
{
    /**
     * @var array
     */
    private $_raConfigParams = [
        'trackingKey'   => [
            'id'    => 'ra_tracking_key',
            'json'  => false
        ],
        'restKey'       => [
            'id'    => 'ra_rest_key',
            'json'  => false,
        ],
        'helpPages'     => [
            'id'    => 'ra_help_pages',
            'json'  => true
        ]
    ];

    /**
     * @var array
     */
    private $_raHooks = [
        'displayHeader',
        'displayFooter'
    ];

    /**
     * @var Builder
     */
    private $_raJSBuilder;

    /**
     * @var int
     */
    private $_raProductMaxImg = 5;

    /**
     * Ra_Tracker constructor.
     */
    public function __construct()
    {
        $this->name                     = 'ra_tracker';
        $this->tab                      = 'analytics_stats';
        $this->version                  = '2.1.0';
        $this->author                   = 'Retargeting BIZ';
        $this->need_instance            = 0;
        $this->bootstrap                = true;
        $this->ps_versions_compliancy   = [
            'min' => '1.7.1.0',
            'max' => _PS_VERSION_
        ];

        parent::__construct();

        $this->displayName      = $this->l('Retargeting Tracker');
        $this->description      = $this->l('Retargeting is a marketing automation tool that boosts the conversion rate and sales of your online store.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Retargeting Tracker? You will lose all the data related to this module.');

        $this->_raJSBuilder = new RetargetingSDK\Javascript\Builder();

        if (!$this->isTrackingApiKeyProvided())
        {
            $this->warning = $this->l('No Tracking API Key provided!');
        }
        else
        {
            $this->_raJSBuilder->setTrackingApiKey($this->getConfigParam('trackingKey'));
        }
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive())
        {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $addConfigFields = function ()
        {
            $response = true;

            foreach ($this->_raConfigParams AS $configParam)
            {
                if (!Configuration::updateValue($configParam['id'], ''))
                {
                    $response = false;

                    break;
                }
            }

            return $response;
        };

        return parent::install() && $addConfigFields() && $this->registerHook($this->_raHooks);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $deleteConfigFields = function ()
        {
            $response = true;

            foreach ($this->_raConfigParams AS $configParam)
            {
                if (!Configuration::deleteByName($configParam['id']))
                {
                    $response = false;

                    break;
                }
            }

            return $response;
        };

        $unregisterHooks = function ()
        {
            $response = true;

            foreach ($this->_raHooks AS $hook)
            {
                if (!$this->unregisterHook($hook))
                {
                    $response = false;

                    break;
                }
            }

            return $response;
        };

        return parent::uninstall() && $deleteConfigFields() && $unregisterHooks();
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
     * @return void
     */
    public function hookDisplayHeader()
    {
        if($this->_raJSBuilder->hasTrackingApiKey())
        {
            $js = [
                'ra_tracker',
                $this->_raJSBuilder->getTrackingSrc(),
                [
                    'position'      => 'bottom',
                    'priority'      => 1001,
                    'inline'        => false,
                    'server'        => 'remote',
                    'attributes'    => 'async'
                ]
            ];

            call_user_func_array([$this->context->controller, 'registerJavascript'], $js);
        }
    }

    /**
     * @param $hookParams
     * @return string|null
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    public function hookDisplayFooter($hookParams)
    {
        $output = null;

        if($this->_raJSBuilder->hasTrackingApiKey())
        {
            $controllersMap = [
                'index'             => [],
                'category'          => [ 'category' ],
                'product'           => [ 'product' ],
                'manufacturer'      => [ 'manufacturer' ],
                'cart'              => [ 'cart' ],
                'orderconfirmation' => [],
                'cms'               => [ 'cms' ],
                'search'            => [ 'search_string' ],
                'pagenotfound'      => []
            ];

            $controller = Tools::getValue('controller');

            if (isset($controllersMap[$controller]))
            {
                $fnParams = [];

                if (!empty($controllersMap[$controller]))
                {
                    foreach ($controllersMap[$controller] AS $fnParam)
                    {
                        $fnParams[] = $hookParams['smarty']->getTemplateVars($fnParam);
                    }
                }

                $this->prepareCartUrlJS();

                call_user_func_array([$this, 'prepare' . ucfirst($controller) . 'JS'], $fnParams);

                $output = $this->_raJSBuilder->generate();
            }
        }

        return $output;
    }

    /**
     * @return void
     */
    protected function prepareIndexJS()
    {
        $this->_raJSBuilder->visitHomePage();
    }

    /**
     * @param $category
     * @throws Exception
     */
    protected function prepareCategoryJS($category)
    {
        if(!empty($category['id']))
        {
            $raCategory = $this->getCategoryData($category['id']);

            if($raCategory instanceof \RetargetingSDK\Category)
            {
                $this->_raJSBuilder->sendCategory($raCategory);
            }
        }
    }

    /**
     * @param $product
     * @throws PrestaShopException
     */
    protected function prepareProductJS($product)
    {
        if(is_object($product) && !empty($product->id))
        {
            $raProduct = new \RetargetingSDK\Product();
            $raProduct->setId($product->id);
            $raProduct->setName($product->name);
            $raProduct->setUrl($this->context->link->getProductLink($product->id));
            $raProduct->setPrice($product->regular_price_amount);

            if(!empty($product->cover['large']['url']))
            {
                $raProduct->setImg($product->cover['large']['url']);
            }
            elseif(!empty($product->images[0]['large']['url']))
            {
                $raProduct->setImg($product->images[0]['large']['url']);
            }

            if(!empty($product->images) && is_array($product->images))
            {
                $images      = [];
                $addedImages = 0;

                foreach ($product->images AS $image)
                {
                    if ($addedImages <= $this->_raProductMaxImg)
                    {
                        if (!empty($image['large']['url']))
                        {
                            $images[] = $image['large']['url'];

                            $addedImages++;
                        }
                    }
                    else break;
                }

                $raProduct->setAdditionalImages($images);
            }

            if(!empty($product->has_discount))
            {
                $raProduct->setPromo($product->price_amount);
            }

            if(!empty($product->id_manufacturer))
            {
                $manufacturerModel = new Manufacturer($product->id_manufacturer, $this->context->language->id);

                if(Validate::isLoadedObject($manufacturerModel))
                {
                    $raProduct->setBrand([
                        'id'    => $manufacturerModel->id,
                        'name'  => $manufacturerModel->name
                    ]);
                }
            }

            if(!empty($product->id_category_default))
            {
                $raCategory = $this->getCategoryData($product->id_category_default);

                if($raCategory instanceof \RetargetingSDK\Category)
                {
                    $raProduct->setCategory([ $raCategory->getData(false) ]);
                }
            }

            $raProduct->setInventory([
                'variations' => false,
                'stock'      => $product->quantity_all_versions > 0
            ]);

            $this->_raJSBuilder->sendProduct($raProduct);
            $this->_raJSBuilder->likeFacebook($raProduct->getId());
        }

    }

    /**
     * @param $manufacturer
     * @return void
     */
    protected function prepareManufacturerJS($manufacturer)
    {
        if(!empty($manufacturer['id']))
        {
            $raBrand = new \RetargetingSDK\Brand();
            $raBrand->setId($manufacturer['id']);
            $raBrand->setName($manufacturer['name']);

            $this->_raJSBuilder->sendBrand($raBrand);
        }
    }

    /**
     * @param $cart
     * @return void
     */
    protected function prepareCartJS($cart)
    {
        if(!empty($cart['products']))
        {
            $productsIds = [];

            foreach ($cart['products'] AS $product)
            {
                $productsIds[] = $product->id;
            }

            $raCheckout = new \RetargetingSDK\Checkout();
            $raCheckout->setProductIds($productsIds);

            $this->_raJSBuilder->checkoutIds($raCheckout);
        }
    }

    /**
     * @return void
     */
    protected function prepareCartUrlJS()
    {
        if(empty($_SESSION['ra_cart_url']))
        {
            $cartUrl = $this->context->link->getPageLink(
                'cart',
                null,
                $this->context->language->id,
                array(
                    'action' => 'show'
                ),
                false,
                null,
                true
            );

            $this->_raJSBuilder->setCartUrl($cartUrl);

            $_SESSION['ra_cart_url'] = true;
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function prepareOrderconfirmationJs()
    {
        $orderId    = (int)Tools::getValue('id_order');
        $orderModel = new Order($orderId);

        if(Validate::isLoadedObject($orderModel))
        {
            $raOrder = new \RetargetingSDK\Order();

            $raOrder->setOrderNo($orderModel->id);

            $orderCustomer = $orderModel->getCustomer();

            $raOrder->setFirstName($orderCustomer->firstname);
            $raOrder->setLastName($orderCustomer->lastname);
            $raOrder->setEmail($orderCustomer->email);
            $raOrder->setShipping($orderModel->total_shipping);
            $raOrder->setTotal($orderModel->total_paid);

            if(!empty($orderCustomer->birthday) || $orderCustomer->birthday == '0000-00-00')
            {
                $raOrder->setBirthday(date('d-m-Y', strtotime($orderCustomer->birthday)));
            }

            // Discounts
            $raOrder->setDiscount($orderModel->total_discounts);

            $discounts = $orderModel->getCartRules();

            if (!empty($discounts))
            {
                $discountCode = array();

                foreach ($discounts as $discount)
                {
                    $cartRule = new CartRule((int)$discount['id_cart_rule']);

                    $discountCode[] = $cartRule->code;
                }

                $raOrder->setDiscountCode($discountCode);
            }

            // Address
            $orderCustomerAddress = new Address($orderModel->id_address_delivery);

            if(Validate::isLoadedObject($orderCustomerAddress))
            {
                $raOrder->setPhone(!empty($orderCustomerAddress->phone_mobile) ? $orderCustomerAddress->phone_mobile : $orderCustomerAddress->phone);
                $raOrder->setState($orderCustomerAddress->country);
                $raOrder->setCity($orderCustomerAddress->city);
                $raOrder->setAddress($orderCustomerAddress->address1);
            }

            foreach ($orderModel->getProducts() AS $product)
            {
                $raOrder->setProduct(
                    $product['product_id'],
                    $product['product_quantity'],
                    $product['total_price_tax_incl'],
                    $product['product_attribute_id']
                );
            }

            $this->_raJSBuilder->saveOrder($raOrder);
        }
    }

    /**
     * @param $page
     * @return void
     */
    protected function prepareCmsJS($page)
    {
        $helpPages = $this->getConfigParam('helpPages');

        if(!empty($page['id']) && in_array($page['id'], $helpPages))
        {
            $this->_raJSBuilder->visitHelpPage();
        }
    }

    /**
     * @param $searchTerm
     * @return void
     */
    protected function prepareSearchJS($searchTerm)
    {
        if(!empty($searchTerm))
        {
            $this->_raJSBuilder->sendSearchTerm($searchTerm);
        }
    }

    /**
     * @return void
     */
    protected function preparePagenotfoundJS()
    {
        $this->_raJSBuilder->pageNotFound();
    }

    /**
     * @param $paramKey
     * @return mixed
     */
    private function getConfigParam($paramKey)
    {
        $paramVal = Configuration::get($this->_raConfigParams[$paramKey]['id']);

        if($this->_raConfigParams[$paramKey]['json'])
        {
            $paramVal = json_decode($paramVal, true);

            if(!is_array($paramVal))
            {
                $paramVal = [];
            }
        }

        return $paramVal;
    }

    /**
     * @return bool
     */
    private function isTrackingApiKeyProvided()
    {
        $trackingKey = $this->getConfigParam('trackingKey');

        return !empty($trackingKey);
    }

    /**
     * @return bool
     */
    private function isRestApiKeyProvided()
    {
        $restKey = $this->getConfigParam('restKey');

        return !empty($restKey);
    }

    /**
     * @return bool
     */
    private function areApiKeysProvided()
    {
        return $this->isTrackingApiKeyProvided() && $this->isRestApiKeyProvided();
    }

    /**
     * @return string
     */
    private function postProcess()
    {
        $response = '';

        if (Tools::isSubmit('raSubmitForm'))
        {
            foreach ($this->_raConfigParams AS $configParam)
            {
                $configParamVal = Tools::getValue($configParam['id']);

                if ($configParam['json'])
                {
                    if (!is_array($configParamVal))
                    {
                        $configParamVal = [ $configParamVal ];
                    }

                    $configParamVal = json_encode($configParamVal);
                }

                Configuration::updateValue($configParam['id'], $configParamVal);
            }

            $response = $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
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

        // Load current value
        $fieldsValue = Configuration::getMultiple(array_column($this->_raConfigParams, 'id'));

        foreach ($this->_raConfigParams AS $configParamKey => $configParam)
        {
            $configParamVal = $fieldsValue[$configParam['id']];

            if ($configParam['json'])
            {
                $configParamVal = json_decode($configParamVal, true);

                if (!is_array($configParamVal))
                {
                    $configParamVal = [];
                }

                $configParam['id'] .= '[]';
            }

            $helper->fields_value[$configParam['id']] = $configParamVal;
        }

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
                    'name'      => $this->_raConfigParams['trackingKey']['id'],
                    'required'  => true
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('REST API Key'),
                    'name'      => $this->_raConfigParams['restKey']['id'],
                    'desc'      => 'Both keys can be found in your <a href="https://retargeting.biz/admin/module/settings/docs-and-api" target="_blank" rel="noopener noreferrer">Retargeting</a> account.'
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
                    'type'      => 'select',
                    'label'     => $this->l('Help Pages'),
                    'name'      => $this->_raConfigParams['helpPages']['id'],
                    'desc'      => $this->l('Choose the pages on which the "visitHelpPage" event should fire.'),
                    'multiple'  => true,
                    'class'     => 'chosen',
                    'options'   => [
                        'query' => CMS::listCMS(),
                        'id'    => 'id_cms',
                        'name'  => 'meta_title'
                    ]
                ]
            ],
            'submit' => [
                'name'  => 'raSubmitForm',
                'title' => $this->l('Save')
            ]
        ];

        return $fields;
    }

    /**
     * @param $categoryId
     * @return \RetargetingSDK\Category
     * @throws Exception
     */
    public function getCategoryData($categoryId)
    {
        $raCategory    = null;
        $categoryModel = new Category($categoryId, $this->context->language->id);

        if(Validate::isLoadedObject($categoryModel))
        {
            $raCategory = new \RetargetingSDK\Category();
            $raCategory->setId($categoryModel->id);
            $raCategory->setName($categoryModel->name);
            $raCategory->setUrl($this->context->link->getCategoryLink($categoryModel->id));

            if(!empty($categoryModel->id_parent))
            {
                $raCategoryBreadcrumbs = [];

                $parentsCategories = $categoryModel->getParentsCategories($this->context->language->id);

                foreach ($parentsCategories AS $pCategoryIdx => $pCategory)
                {
                    if(
                        isset($pCategory['id_category'])
                        && is_string($pCategory['name'])
                        && (int)$pCategory['active'] === 1
                        && $pCategory['id_category'] != $categoryModel->id
                        && $pCategory['is_root_category'] < 1
                    )
                    {
                        $parentId = $pCategory['id_parent'];

                        if(!empty($parentsCategories[$pCategoryIdx + 1]) && $parentsCategories[$pCategoryIdx + 1]['is_root_category'] > 0)
                        {
                            $parentId = false;
                        }

                        $raCategoryBreadcrumbs[] = [
                            'id'     => $pCategory['id_category'],
                            'name'   => $pCategory['name'],
                            'parent' => $parentId
                        ];
                    }
                }

                if(!empty($raCategoryBreadcrumbs))
                {
                    $raCategory->setParent($categoryModel->id_parent);
                    $raCategory->setBreadcrumb($raCategoryBreadcrumbs);
                }
            }
        }

        return $raCategory;
    }
}