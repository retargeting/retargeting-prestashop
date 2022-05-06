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
 * @copyright 2014-2022 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class RTGOrderModel
 */
class RTGOrderModel extends \RetargetingSDK\Order
{
    /**
     * RTGOrderModel constructor.
     * @param $oderId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    public function __construct($oderId)
    {
        $this->_setOrderData($oderId);
    }

    /**
     * @param $orderId
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    private function _setOrderData($orderId)
    {
        $order = new Order($orderId);

        if (Validate::isLoadedObject($order)) {
            $this->setOrderNo($order->id);

            $orderCustomer = $order->getCustomer();

            $order->total_paid = RTGContextHelper::convertCurrency($order->total_paid);

            $this->setFirstName($orderCustomer->firstname);
            $this->setLastName($orderCustomer->lastname);
            $this->setEmail($orderCustomer->email);
            $this->setShipping(round($order->total_shipping, 2));
            $this->setTotal($order->total_paid);

            if (!empty($orderCustomer->birthday) || $orderCustomer->birthday == '0000-00-00') {
                $this->setBirthday(date('d-m-Y', strtotime($orderCustomer->birthday)));
            }

            // Discounts
            $this->setDiscount($order->total_discounts);

            $discounts = $order->getCartRules();

            if (!empty($discounts)) {
                $discountCode = array();

                foreach ($discounts as $discount) {
                    $cartRule = new CartRule((int)$discount['id_cart_rule']);

                    $discountCode[] = $cartRule->code;
                }

                $this->setDiscountCode($discountCode);
            }

            // Address
            $orderCustomerAddress = new Address($order->id_address_delivery);

            if (Validate::isLoadedObject($orderCustomerAddress)) {
                $this->setPhone(!empty($orderCustomerAddress->phone_mobile)
                    ? $orderCustomerAddress->phone_mobile
                    : $orderCustomerAddress->phone);
                $this->setState($orderCustomerAddress->country);
                $this->setCity($orderCustomerAddress->city);
                $this->setAddress($orderCustomerAddress->address1);
            }

            foreach ($order->getProducts() as $product) {

                $product['total_price_tax_incl'] = RTGContextHelper::convertCurrency($product['total_price_tax_incl']);

                $this->setProduct(
                    $product['product_id'],
                    $product['product_quantity'],
                    $product['total_price_tax_incl'],
                    // $product['product_attribute_id']
                    ''
                );
            }
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Fail to load order with id: ' . $orderId);
        }
    }
}
