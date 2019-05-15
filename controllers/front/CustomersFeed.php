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
 * Class rtg_trackerCustomersFeedModuleFrontController
 */
class rtg_trackerCustomersFeedModuleFrontController extends ModuleFrontController
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
     * @var bool
     */
    private $_onlyActive = false;

    /**
     * @var null
     */
    private $_token = null;

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
            if(!empty($this->_token))
            {
                $raCustomersFeed = new \RetargetingSDK\CustomersFeed($this->_token);

                foreach ($this->getCustomers() AS $customer)
                {
                    $raCustomer = new \RetargetingSDK\Customer();
                    $raCustomer->setFirstName($customer['firstname']);
                    $raCustomer->setLastName($customer['lastname']);
                    $raCustomer->setEmail($customer['email']);
                    $raCustomer->setStatus($customer['active'] == 1);

                    // TO DO
                    // $raCustomer->setPhone(null);

                    $raCustomersFeed->addCustomer($raCustomer->getData(true));
                }

                // Module link with per_page param
                $moduleLink = RTGLinkHelper::getModuleLink('CustomersFeed', [ 'per_page' => $this->_perPage ]);

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

                $raCustomersFeed->setCurrentPage($this->_currentPage);
                $raCustomersFeed->setPrevPage($moduleLink . '&page=' . $prevPage);
                $raCustomersFeed->setNextPage($moduleLink . '&page=' . $nextPage);
                $raCustomersFeed->setLastPage($this->_lastPage);

                echo $raCustomersFeed->getData();
            }
            else
            {
                echo 'Token arg is missing or is empty!';
            }
        }
        else
        {
            echo 'This feed is disabled!';
        }

        exit(0);
    }

    /**
     * @return array|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    protected function getCustomers()
    {
        $offset = ($this->_currentPage - 1) * $this->_perPage;
        $limit  = $this->_perPage;

        $sql  = 'SELECT `id_customer`, `email`, `firstname`, `lastname`, `active` ';
        $sql .= 'FROM `' . _DB_PREFIX_ . 'customer` ';
        $sql .= 'WHERE 1 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER) . ' ';

        if($this->_onlyActive)
        {
            $sql .= 'AND `active` = 1 ';
        }

        $sql .= 'ORDER BY `id_customer` ASC ';
        $sql .= 'LIMIT ' . $offset . ', ' . $limit;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
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

        // Token
        $token = Tools::getValue('token');

        if(!empty($token))
        {
            $this->_token = $token;
        }
    }

    /**
     * Set total rows
     */
    private function setTotalRows()
    {
        $sql  = 'SELECT COUNT(DISTINCT c.`id_customer`) AS total ';
        $sql .= 'FROM `'._DB_PREFIX_.'customer` c';

        if($this->_onlyActive)
        {
            $sql .= '  WHERE c.`active` = 1';
        }

        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        $this->_totalRows = $row['total'];
        $this->_lastPage  = $row['total'] > 0 ? ceil($row['total'] / $this->_perPage) : 1;
    }

    /**
     * @return bool
     */
    private function isFeedEnabled()
    {
        $paramVal = RTGConfigHelper::getParamValue('customersFeed');

        return (int) $paramVal > 0;
    }
}