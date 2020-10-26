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
 * Class Rtg_trackerProductsFeedModuleFrontController
 */
class Rtg_trackerProductsFeedModuleFrontController extends ModuleFrontController
{
    private $order_by = 'id_product';
    private $order_way = 'DESC';
    private $id_category = false;
    private $only_active = true;
    protected $context = null;
    private $limit = 250;

    private $id_lang;

    public function __construct()
    {
        parent::__construct();
        $this->id_lang = RTGContextHelper::getLanguageId();

    }

    /**
     * Display products list
     *
     * @throws Exception
     */
    public function initContent()
    {
        if ($this->isFeedEnabled()) {
            $this->getProductBatches();
        } else {
            echo 'This feed is disabled!';
        }

        exit(0);
    }

    private function getProductBatches()
    {

        header("Content-Disposition: attachment; filename=retargeting.csv");
        header("Content-type: text/csv");

        $id_lang = $this->id_lang;

        $start = 0;

        $outstream = fopen('php://output', 'w');

        $loop = true;

        fputcsv($outstream, array(
            'product id',
            'product name',
            'product url',
            'image url',
            'stock',
            'price',
            'sale price',
            'brand',
            'category',
            'extra data'
        ), ',', '"');

        do {
            $batch = Product::getProducts(
                $this->id_lang,
                $start,
                $this->limit,
                $this->order_by,
                $this->order_way,
                $this->id_category,
                $this->only_active,
                $this->context
            );

            if(sizeof($batch) == 0) {
                $loop = false;
            }



            foreach ($batch as $_product) {
                $extra_data = [
                    'categories' => '',
                    'media gallery' => [],
                    'variations' => [],
                    'margin' => null
                ];

                $product = new Product($_product['id_product'], false, $this->id_lang);
                $manufacturer = new Manufacturer($product->id_manufacturer, $this->id_lang);
                $category = new Category($product->id_category_default, $this->id_lang);
                $categories = $category->getParentsCategories($this->id_lang);
                $ctree = [];

                foreach($categories as $c) {
                    if($c['is_root_category'] == "1") {
                        continue;
                    }
                    $ctree[] = $c['name'];
                }

                $images = $this->getProductImages($product);

                $extra_data['categories'] =  implode(' | ', $ctree);
                $extra_data['media gallery'] =  $images['extra'];


                $pprice = number_format($product->getPriceWithoutReduct(), 2);
                $psprice = number_format($product->getPrice(), 2);
                if(
                    empty($category->name) ||
                    empty($product->name) ||
                    empty(RTGLinkHelper::getProductLink($product)) ||
                    empty($images['main']) ||
                    $pprice == 0 ||
                    $psprice == 0
                ) {
                    continue;
                }

                fputcsv($outstream, array(
                    'product id' => $product->id,
                    'product name' => $product->name,
                    'product url' => RTGLinkHelper::getProductLink($product),
                    'image url' => $images['main'],
                    'stock' => Product::getQuantity($_product['id_product']),
                    'price' => $pprice,
                    'sale price' => $psprice,
                    'brand' => $manufacturer->name,
                    'category' => $category->name,
                    'extra data' => json_encode($extra_data, JSON_UNESCAPED_SLASHES)
                ), ',', '"');
            }

            $start += $this->limit;
        } while ($loop);
    }

    /**
     * @return bool
     */
    private function isFeedEnabled()
    {
        $paramVal = Configuration::get('rtg_products_feed');

        return (int) $paramVal > 0;
    }

    private function getProductImages($product)
    {
        $result = [
            'main' => '',
            'extra' => []
        ];
        $imageId    = null;
        $imagesIds  = [];
        $attrImages = [];
        $defaultId  = $product->getDefaultIdProductAttribute();

        if ($defaultId) {
            $attrImages = Product::_getAttributeImageAssociations($defaultId);
        }

        $coverImageId = $product->getCoverWs();

        if ((int)$coverImageId > 0) {
            if (!$attrImages || in_array($coverImageId, $attrImages))
            {
                $imageId = $coverImageId;
            }
        }

        if (!$imageId && $attrImages) {
            foreach ($attrImages as $attrImageId) {
                if ((int)$attrImageId > 0) {
                    $imageId = $attrImageId;

                    break;
                }
            }
        }

        $productImages = $product->getImages($this->id_lang);

        if ($productImages) {
            foreach ($productImages as $productImage) {
                $productImageId = (int)$productImage['id_image'];

                if ($productImageId > 0) {
                    if(!$imageId) {
                        $imageId = $productImageId;
                    } elseif($imageId != $productImageId) {
                        $imagesIds[] = $productImageId;
                    }
                }
            }
        }

        if ((int)$imageId > 0) {
            $url = RTGLinkHelper::getImageLink($product->link_rewrite, $product->id . '-' . $imageId);

            $result['main'] = $url;
        }

        if (!empty($imagesIds)) {
            foreach ($imagesIds as $imgIdx => $imgId) {
                $result['extra'][] = RTGLinkHelper::getImageLink($product->link_rewrite, $product->id . '-' . $imgId);
            }
        }

        return $result;
    }


}
