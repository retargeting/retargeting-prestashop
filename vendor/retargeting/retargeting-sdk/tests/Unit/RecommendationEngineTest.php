<?php
/**
 * Created by PhpStorm.
 * User: andreicotaga
 * Date: 2019-05-29
 * Time: 11:06
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RetargetingSDK\RecommendationEngine;

/**
 * Class RecommendationEngineTest
 * @package Tests\Unit
 * @property RecommendationEngine engine
 */
class RecommendationEngineTest extends TestCase
{
    public function setUp(): void
    {
        $this->engine = new RecommendationEngine();
    }

    /**
     * Test if mark home page returns correct div with correct id
     */
    public function test_if_mark_home_page_return_correct_value()
    {
        $this->engine->markHomePage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-home-page"></div>');
    }

    /**
     * Test if mark category page returns correct div with correct id
     */
    public function test_if_mark_category_page_return_correct_value()
    {
        $this->engine->markCategoryPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-category-page"></div>');
    }

    /**
     * Test if mark product page returns correct div with correct id
     */
    public function test_if_mark_product_page_return_correct_value()
    {
        $this->engine->markProductPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-product-page"></div>');
    }

    /**
     * Test if mark checkout page returns correct div with correct id
     */
    public function test_if_mark_checkout_page_return_correct_value()
    {
        $this->engine->markCheckoutPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-checkout-page"></div>');
    }

    /**
     * Test if mark thank you page returns correct div with correct id
     */
    public function test_if_mark_thankyou_page_return_correct_value()
    {
        $this->engine->markThankYouPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-thank-you-page"></div>');
    }

    /**
     * Test if mark out of stock page returns correct div with correct id
     */
    public function test_if_mark_out_of_stock_page_return_correct_value()
    {
        $this->engine->markOutOfStockPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-out-of-stock-page"></div>');
    }

    /**
     * Test if mark search page returns correct div with correct id
     */
    public function test_if_mark_search_page_return_correct_value()
    {
        $this->engine->markSearchPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-search-page"></div>');
    }

    /**
     * Test if mark not found page returns correct div with correct id
     */
    public function test_if_mark_notfound_page_return_correct_value()
    {
        $this->engine->markNotFoundPage();

        $this->assertEquals($this->engine->generateTags(), '<div id="retargeting-recommeng-not-found-page"></div>');
    }

    /**
     * Test if generate tags returns string format data
     */
    public function test_if_generate_tags_returns_string()
    {
        $this->engine->markSearchPage();

        $this->assertIsString($this->engine->generateTags());
    }
}