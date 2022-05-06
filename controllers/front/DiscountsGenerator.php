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
 * Class RtgDiscountsGeneratorModuleFrontController
 */
class RtgtrackerDiscountsGeneratorModuleFrontController extends ModuleFrontController
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
     * @var string
     */
    private $discountType;

    /**
     * @var array
     */
    private $discountTypes = [
        'reduction_amount',
        'reduction_percent',
        'free_shipping'
    ];

    /**
     * @var float|int|bool
     */
    private $discountValue;

    /**
     * @var int
     */
    private $discountNum;

    /**
     * @var int
     */
    private $discountNumMax = 5000;

    /**
     * Display products list
     *
     * @throws Exception
     */
    public function initContent()
    {
        if ($this->isGeneratorEnabled()) {
            $discountCodes = [];

            $discountAttempts    = 0;
            $discountMaxAttempts = $this->discountNum <= 2000 ? 1000 : ceil($this->discountNum / 2);

            $stringGenerator = new RTGRandomStringGenerator();
            $stringGenerator->setAlphabet(
                implode(range('A', 'Z'))
                . implode(range(0, 9))
            );

            while (count($discountCodes) < $this->discountNum && $discountAttempts < $discountMaxAttempts) {
                $discountCode = $stringGenerator->generate(15);
                $discountName = 'RA-' . $discountCode;
                $discountDesc = 'Cart rule created by Retargeting: ' . $discountName;

                $cartRule = new CartRuleCore();
                $cartRule->name = [];

                foreach (RTGContextHelper::getLanguages('id_lang') as $lang) {
                    $cartRule->name[$lang] = $discountName;
                }

                $cartRule->description           = $discountDesc;
                $cartRule->code                  = $discountCode;
                $cartRule->active                = 1;
                $cartRule->date_from             = date('Y-m-d H:i:s');
                $cartRule->date_to               = date(
                    'Y-m-d h:i:s',
                    mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1)
                );
                $cartRule->quantity              = 1;
                $cartRule->quantity_per_user     = 1;
                $cartRule->partial_use           = false;
                $cartRule->cart_rule_restriction = true;
                $cartRule->{$this->discountType} = $this->discountValue;

                if ($cartRule->add()) {
                    $discountCodes[] = $discountCode;
                } else {
                    $discountAttempts++;
                }
            }

            $this->outputResponse($discountCodes, true);
        } else {
            $this->outputResponse('Provided parameters are wrong!');
        }
    }

    /**
     * @return bool
     */
    private function validateReqParams()
    {
        $type = Tools::getValue('type');

        if (in_array($type, $this->discountTypes)) {
            $this->discountType = $type;
        } else {
            return false;
        }

        $value = (float)Tools::getValue('value');

        if ($this->discountType == 'free_shipping') {
            $this->discountValue = true;
        } elseif ($value > 0) {
            $this->discountValue = $value;
        } else {
            return false;
        }

        $num = (int)Tools::getValue('num');

        if ($num > 0 && $num <= $this->discountNumMax) {
            $this->discountNum = $num;
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isGeneratorEnabled()
    {
        $storedRestKey   = RTGConfigHelper::getParamValue('restKey');
        $providedRestKey = Tools::getValue('key');

        return !empty($storedRestKey)
            && !empty($providedRestKey)
            && $storedRestKey == $providedRestKey
            && $this->validateReqParams();
    }

    /**
     * @param $content
     * @param bool $status
     * @return false|string
     */
    private function outputResponse($content, $status = false)
    {
        $response = [
            'status' => $status
        ];

        if (!$status) {
            $response['error'] = $content;
        } else {
            $response['data'] = $content;
        }

        echo json_encode($response);

        exit(0);
    }
}
