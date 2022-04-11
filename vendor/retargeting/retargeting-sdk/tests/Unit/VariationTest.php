<?php
/**
 * Created by PhpStorm.
 * User: andreicotaga
 * Date: 2019-05-29
 * Time: 10:39
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RetargetingSDK\Variation;

/**
 * Class VariationTest
 * @package Tests\Unit
 * @property Variation variation
 */
class VariationTest extends TestCase
{
    public function setUp(): void
    {
        $this->variation = new Variation();

        $details = [
            42 => [
                'category_name' => 'Size',
                'category' => 'size',
                'value' => 42
            ],
            'B' => [
                'category_name' => 'Color',
                'category' => 'color',
                'value' => 'Black'
            ],
        ];

        $this->variation->setStock(true);
        $this->variation->setCode('42-B');
        $this->variation->setDetails($details);
    }

    /**
     * Test if variation code is not null
     */
    public function test_if_variation_has_code()
    {
        $this->assertNotNull($this->variation->getCode());
    }

    /**
     * Test if stock is boolean type of
     */
    public function test_if_stock_is_boolean()
    {
        $this->assertIsBool($this->variation->getStock());
    }

    /**
     * Test if details is array type of
     */
    public function test_if_details_is_array()
    {
        $this->assertIsArray($this->variation->getDetails());
    }

    /**
     * Test if details is array or not
     */
    public function test_if_details_is_not_empty()
    {
        $this->assertNotEmpty($this->variation->getDetails());
    }

    /**
     * Test if get data returns correct format array
     */
    public function test_if_get_data_variation_function_return_correct_format_array()
    {
        $variation = [
            'code'      => $this->variation->getCode(),
            'stock'     => $this->variation->getStock(),
            'details'   => $this->variation->getDetails()
        ];

        $this->assertEquals($this->variation->getData(false), $variation);
    }

    /**
     * Test if get data returns correct format json
     */
    public function test_if_get_data_variation_function_return_correct_format_json()
    {
        $variation = [
            'code'      => $this->variation->getCode(),
            'stock'     => $this->variation->getStock(),
            'details'   => $this->variation->getDetails()
        ];

        $this->assertEquals($this->variation->getData(), json_encode($variation, JSON_PRETTY_PRINT));
    }
}