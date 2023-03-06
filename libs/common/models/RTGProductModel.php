<?php
/**
 * 2014-2023 Retargeting BIZ SRL.
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
 * @copyright 2014-2023 Retargeting SRL
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Class RTGProductModel.
 */
class RTGProductModel extends \RetargetingSDK\Product
{
    /**
     * RTGProductModel constructor.
     *
     * @param mixed $productId
     *
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    public function __construct($productId)
    {
        $this->setProductData($productId);
    }

    /**
     * @param mixed $productId
     *
     * @throws PrestaShopException
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    private function setProductData($productId)
    {
        $product = new Product($productId, false, RTGConfigHelper::getParamValue('defaultLanguage'));

        if (Validate::isLoadedObject($product)) {
            $this->setId($product->id);
            $this->setName($product->name);
            $this->setUrl(RTGLinkHelper::getProductLink($product));
            $this->setProductImages($product);
            $this->setProductPrices($product);
            $this->setProductManufacturer($product->id_manufacturer);
            $this->setProductCategory($product->id_category_default);
            $this->setInventory([
                'variations' => false,
                'stock' => $product->checkQty(1),
            ]);
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Fail to load product with id: ' . $productId);
        }
    }

    /**
     * @param Product $product
     *
     * @throws Exception
     */
    private function setProductImages($product)
    {
        $imageId = null;
        $imagesIds = [];
        $attrImages = [];
        $defaultId = $product->getDefaultIdProductAttribute();

        if ($defaultId) {
            $attrImages = Product::_getAttributeImageAssociations($defaultId);
        }

        $coverImageId = $product->getCoverWs();

        if ((int) $coverImageId > 0) {
            if (!$attrImages || in_array($coverImageId, $attrImages)) {
                $imageId = $coverImageId;
            }
        }

        if (!$imageId && $attrImages) {
            foreach ($attrImages as $attrImageId) {
                if ((int) $attrImageId > 0) {
                    $imageId = $attrImageId;

                    break;
                }
            }
        }

        $productImages = $product->getImages(RTGContextHelper::getLanguageId());

        if ($productImages) {
            foreach ($productImages as $productImage) {
                $productImageId = (int) $productImage['id_image'];

                if ($productImageId > 0) {
                    if (!$imageId) {
                        $imageId = $productImageId;
                    } elseif ($imageId != $productImageId) {
                        $imagesIds[] = $productImageId;
                    }
                }
            }
        }

        if ((int) $imageId > 0) {
            $url = RTGLinkHelper::getImageLink($product->link_rewrite, $product->id . '-' . $imageId);

            $this->setImg($url);
        }

        if (!empty($imagesIds)) {
            foreach ($imagesIds as $imgIdx => $imgId) {
                $imagesIds[$imgIdx] = RTGLinkHelper::getImageLink($product->link_rewrite, $product->id . '-' . $imgId);
            }

            $this->setAdditionalImages($imagesIds);
        }
    }

    /**
     * @param Product $product
     *
     * @throws Exception
     *
     * Deprecated:
     * since 1.7.4 use convertPriceToCurrency()
     */
    private function setProductPrices($product)
    {
        $regularPrice = $product->getPrice(true, null, 6, null, false, false);
        $promoPrice = $product->getPrice();

        $regularPrice = RTGContextHelper::convertCurrency($regularPrice);
        $promoPrice = RTGContextHelper::convertCurrency($promoPrice);

        $this->setPrice($regularPrice);

        if ($promoPrice < $regularPrice) {
            $this->setPromo($promoPrice);
        }
    }

    /**
     * @param mixed $manufacturerId
     *
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    private function setProductManufacturer($manufacturerId)
    {
        if (!empty($manufacturerId)) {
            $RTGManufacturer = new RTGManufacturerModel($manufacturerId);

            $this->setBrand($RTGManufacturer->getData(false));
        }
    }

    /**
     * @param mixed $categoryId
     *
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    private function setProductCategory($categoryId)
    {
        if (!empty($categoryId)) {
            $RTGCategory = new RTGCategoryModel($categoryId);

            $this->setCategory([$RTGCategory->getData(false)]);
        }
    }
}
