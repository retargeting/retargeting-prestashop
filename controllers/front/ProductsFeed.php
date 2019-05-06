<?php

require_once( __DIR__ . '/../../vendor/autoload.php');

/**
 * Class ra_trackerProductsFeedModuleFrontController
 */
class ra_trackerProductsFeedModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $auth = false;

    /**
     * @var bool
     */
    public $guestAllowed = true;

    /**
     * @var int
     */
    private $_currentPage = 1;

    /**
     * @var int
     */
    private $_lastPage = 1;

    /**
     * @var int
     */
    private $_perPage = 100;

    /**
     * @var int
     */
    private $_totalRows = 0;

    /**
     * ra_trackerProductsFeedModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->validateReqParams();
        $this->setTotalRows();
    }

    /**
     * Display products list
     *
     * @throws Exception
     */
    public function initContent()
    {
        if($this->isFeedEnabled())
        {
            $raProductFeed = new \RetargetingSDK\ProductFeed();

            foreach ($this->getProducts() AS $product)
            {
                $raProduct = new \RetargetingSDK\Product();
                $raProduct->setId($product['id_product']);
                $raProduct->setName($product['name']);
                $raProduct->setUrl($product['link']);
                $raProduct->setPrice($product['price_without_reduction']);

                $image = Image::getCover($product['id_product']);

                $raProduct->setImg(
                    $this->context->link->getImageLink($product['link_rewrite'], $image['id_image'], 'large')
                );

                if($product['price_without_reduction'] != $product['price'])
                {
                    $raProduct->setPromo($product['price']);
                }

                if(!empty($product['id_manufacturer']) && !empty($product['manufacturer_name']))
                {
                    $raProduct->setBrand([
                        'id'    => $product['id_manufacturer'],
                        'name'  => $product['manufacturer_name']
                    ]);
                }

                if(!empty($product['id_category_default']))
                {
                    $raCategory = $this->getCategory($product['id_category_default']);

                    if($raCategory instanceof \RetargetingSDK\Category)
                    {
                        $raProduct->setCategory([ $raCategory->getData(false) ]);
                    }
                }

                $raProduct->setInventory([
                    'variations' => false,
                    'stock'      => $product['quantity_all_versions'] > 0
                ]);

                $raProductFeed->addProduct($raProduct->getData(false));
            }

            // Module link with per_page param
            $moduleLink = $this->context->link->getModuleLink('ra_tracker', 'ProductFeed', [
                'per_page' => $this->_perPage
            ], true);

            // Previous page
            $prevPage = $this->_currentPage - 1;

            if($prevPage < 1)
            {
                $prevPage = $this->_currentPage;
            }

            // Next page
            $nextPage = $this->_currentPage + 1;

            if($nextPage > $this->_lastPage)
            {
                $nextPage = $this->_lastPage;
            }

            $raProductFeed->setCurrentPage($this->_currentPage);
            $raProductFeed->setPrevPage($moduleLink . '&page=' . $prevPage);
            $raProductFeed->setNextPage($moduleLink . '&page=' . $nextPage);
            $raProductFeed->setLastPage($this->_lastPage);

            echo $raProductFeed->getData();
        }
        else
        {
            echo 'This feed is disabled!';
        }

        exit(0);
    }

    /**
     * Get products list
     *
     * @return array
     */
    protected function getProducts()
    {
        $products = Product::getProducts(
            (int)$this->context->language->id,
            ( ($this->_currentPage - 1) * $this->_perPage ),
            $this->_perPage,
            'id_product',
            'ASC',
            false,
            true,
            null
        );

        return Product::getProductsProperties(
            $this->context->language->id,
            $products
        );
    }

    /**
     * @param $categoryId
     * @return \RetargetingSDK\Category
     * @throws Exception
     */
    protected function getCategory($categoryId)
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

    /**
     * Validate request params
     */
    private function validateReqParams()
    {
        // Current page
        $currentPage = (int) Tools::getValue('page');

        if($currentPage > 0)
        {
            $this->_currentPage = $currentPage;
        }

        // Per page
        $perPage = (int) Tools::getValue('per_page');

        if($perPage > 0 && $perPage <= 500)
        {
            $this->_perPage = $perPage;
        }
    }

    /**
     * Set total rows
     */
    private function setTotalRows()
    {
        $result = Db::getInstance()->getRow('SELECT COUNT(DISTINCT p.`id_product`) AS total FROM `'._DB_PREFIX_.'product` p WHERE p.`active` = 1');

        $this->_totalRows = $result['total'];
        $this->_lastPage  = $result['total'] > 0 ? ceil($result['total'] / $this->_perPage) : 1;
    }

    /**
     * @return bool
     */
    private function isFeedEnabled()
    {
        $paramVal = Configuration::get('ra_products_feed');

        return (int) $paramVal > 0;
    }
}