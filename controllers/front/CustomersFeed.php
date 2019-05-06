<?php


require_once( __DIR__ . '/../../vendor/autoload.php');

/**
 * Class ra_trackerCustomersFeedModuleFrontController
 */
class ra_trackerCustomersFeedModuleFrontController extends ModuleFrontController
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
            $raCustomersFeed = new \RetargetingSDK\CustomersFeed('test');

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
            $moduleLink = $this->context->link->getModuleLink('ra_tracker', 'CostumersFeed', [
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

            $raCustomersFeed->setCurrentPage($this->_currentPage);
            $raCustomersFeed->setPrevPage($moduleLink . '&page=' . $prevPage);
            $raCustomersFeed->setNextPage($moduleLink . '&page=' . $nextPage);
            $raCustomersFeed->setLastPage($this->_lastPage);

            echo $raCustomersFeed->getData();
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

        $result = Db::getInstance()->getRow($sql);

        $this->_totalRows = $result['total'];
        $this->_lastPage  = $result['total'] > 0 ? ceil($result['total'] / $this->_perPage) : 1;
    }

    /**
     * @return bool
     */
    private function isFeedEnabled()
    {
        $paramVal = Configuration::get('ra_customers_feed');

        return (int) $paramVal > 0;
    }
}