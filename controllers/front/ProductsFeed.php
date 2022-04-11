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
 * @copyright 2014-2021 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class RtgProductsFeedModuleFrontController
 */
class Rtg_trackerProductsFeedModuleFrontController extends ModuleFrontController
{
    private $order_by = 'id_product';
    private $order_way = 'ASC';
    private $id_category = false;
    private $only_active = true;
    protected $context = null;
    private $limit = 250;

    private $filename = 'retargeting.csv';

    private $file = [];
    private $is = 'live';

    private $id_lang;

    public function __construct()
    {
        parent::__construct();
        $this->id_lang = RTGContextHelper::getLanguageId();
        $this->file['cron'] = [
            _PS_MODULE_DIR_ . 'rtg_tracker/'.$this->filename.'.tmp',
            'w+',
            _PS_MODULE_DIR_ . 'rtg_tracker/'.$this->filename
        ];
        $this->file['live'] = [
            'php://output',
            'w'
        ];
        $this->file['static'] = [
            _PS_MODULE_DIR_ . 'rtg_tracker/'.$this->filename,
            'r'
        ];
        if (Tools::getIsset('cron')) {
            $this->is = 'cron';
        } elseif (Tools::getIsset('static')) {
            $this->is = 'static';
        }
        if ($this->is !== 'cron') {
            header('Content-Disposition: attachment; filename='.$this->filename);
            header("Content-type: text/csv; charset=utf-8");
        }
    }

    /**
     * Display products list
     *
     * @throws Exception
     */
    public function initContent()
    {
        if ($this->isFeedEnabled()) {
            if ($this->is === 'static') {
                if (file_exists($this->file['static'][0])) {
                    $this->getStatic();
                } else {
                    $this->is = 'cron';
                    $this->getProductBatches();
                }
            } else {
                $this->getProductBatches();
            }
        } else {
            echo 'This feed is disabled!';
        }

        exit(0);
    }
    private function getStatic()
    {

        $outstream = fopen($this->file['static'][0], $this->file['static'][1]);

        if (false === $outstream) {
            exit("fail");
        }

        echo fread($outstream, filesize($this->file['static'][0]));
        
        fclose($outstream);
    }

    private function getProductBatches()
    {
        $start = 0;
        $is = $this->is;

        $outstream = fopen($this->file[$is][0], $this->file[$is][1]);

        $defLanguage = RTGConfigHelper::getParamValue('defaultLanguage');
        $defStock = RTGConfigHelper::getParamValue('stockStatus');
        $defStock = empty($defStock) ? 0 : $defStock;

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

            if (sizeof($batch) == 0) {
                $loop = false;
            }

            foreach ($batch as $_product) {
                $extra_data = [
                    'categories' => [],
                    'media_gallery' => [],
                    'variations' => [],
                    'margin' => null
                ];

                $product = new Product($_product['id_product'], false, $defLanguage);
                $manufacturer = new Manufacturer($product->id_manufacturer, $defLanguage);
                $category = new Category($product->id_category_default, $defLanguage);
                $categories = $category->getParentsCategories($defLanguage);

                $category->id = is_array($category->id) ? $category->id[0] : $category->id;
                $category->name = is_array($category->name) ? $category->name[0] : $category->name;

                $category->name = empty($category->name) ? "Root" : $category->name;
                $ctree = [];
                foreach ($categories as $c) {
                    if ($c['name'] !== null) {
                        $ctree[$c['id_category']] = $c['name'];
                    }
                }
                unset($ctree[$category->id]);

                $ctree[$category->id] = $category->name;

                $images = $this->getProductImages($product);
                
                $extra_data['categories'] = $ctree;

                $extra_data['media_gallery'] =  $images['extra'];

                $pprice = number_format($product->getPriceWithoutReduct(), 2, '.', '');
                $psprice = number_format($product->getPrice(), 2, '.', '');

                $link = RTGLinkHelper::getProductLink($product);
                
                if (empty($product->name) ||
                    empty($link) ||
                    empty($images['main']) || ( empty((float) $pprice) && empty((float) $psprice) )) {
                    continue;
                }

                $pprice = empty((float) $pprice) && !empty((float) $psprice) ?
                    $psprice : $pprice;
                
                $psprice = empty((float) $psprice) ? $pprice : $psprice;

                $pprice = (float) $psprice >= (float) $pprice ? $psprice : $pprice;
                
                $stock = Product::getQuantity($_product['id_product']);
               
                fputcsv($outstream, array(
                    'product id' => $product->id,
                    'product name' => is_array($product->name) ? $product->name[1] : $product->name,
                    'product url' => $link,
                    'image url' => $images['main'],
                    'stock' => $stock < 0 ? $defStock : $stock,
                    'price' => $pprice,
                    'sale price' => $psprice,
                    'brand' => $manufacturer->name,
                    'category' => $category->name,
                    'extra data' => json_encode($extra_data, JSON_UNESCAPED_SLASHES)
                ), ',', '"');
            }

            $start += $this->limit;
        } while ($loop);
        
        fclose($outstream);

        if ($is === 'cron') {
            copy($this->file[$is][0], $this->file[$is][2]);
            
            header('Content-Type: text/json');

            echo json_encode(['status'=>'succes']);
        }
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
            if (!$attrImages || in_array($coverImageId, $attrImages)) {
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
                    if (!$imageId) {
                        $imageId = $productImageId;
                    } elseif ($imageId != $productImageId) {
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
            foreach ($imagesIds as $imgId) {
                $result['extra'][] = RTGLinkHelper::getImageLink($product->link_rewrite, $product->id . '-' . $imgId);
            }
        }
        
        return $result;
    }
}
