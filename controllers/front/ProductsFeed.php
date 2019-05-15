<?php
/**
 * 2014-2019 Retargeting BIZ SRL
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
 * @copyright 2014-2019 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class rtg_trackerProductsFeedModuleFrontController
 */
class rtg_trackerProductsFeedModuleFrontController extends ModuleFrontController
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
            $RTGProductFeed = new \RetargetingSDK\ProductFeed();

            foreach ($this->getProductIds() AS $productId)
            {
                $RTGProduct = new RTGProductModel($productId);

                $RTGProductFeed->addProduct($RTGProduct->getData(false));
            }

            // Module link with per_page param
            $moduleLink = RTGLinkHelper::getModuleLink('ProductFeed', [ 'per_page' => $this->_perPage ]);

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

            $RTGProductFeed->setCurrentPage($this->_currentPage);
            $RTGProductFeed->setPrevPage($moduleLink . '&page=' . $prevPage);
            $RTGProductFeed->setNextPage($moduleLink . '&page=' . $nextPage);
            $RTGProductFeed->setLastPage($this->_lastPage);

            echo $RTGProductFeed->getData();
        }
        else
        {
            echo 'This feed is disabled!';
        }

        exit(0);
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function getProductIds()
    {
        $sql  = 'SELECT `id_product` ';
        $sql .= 'FROM `' . _DB_PREFIX_ . 'product` ';
        $sql .= 'WHERE `active` = 1 AND `available_for_order` = 1 ';
        $sql .= 'LIMIT ' . ( ($this->_currentPage - 1) * $this->_perPage ) . ', ' . $this->_perPage;

        $rows = DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return array_column($rows, 'id_product');
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
        $sql  = 'SELECT COUNT(DISTINCT p.`id_product`) AS total ';
        $sql .= 'FROM `' . _DB_PREFIX_ . 'product` p ';
        $sql .= 'WHERE `active` = 1 AND `available_for_order` = 1';

        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        $this->_totalRows = $row['total'];
        $this->_lastPage  = $row['total'] > 0 ? ceil($row['total'] / $this->_perPage) : 1;
    }

    /**
     * @return bool
     */
    private function isFeedEnabled()
    {
        $paramVal = Configuration::get('rtg_products_feed');

        return (int) $paramVal > 0;
    }
}