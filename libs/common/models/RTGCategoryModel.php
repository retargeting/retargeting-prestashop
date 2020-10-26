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
 * Class RTGCategoryModel
 */
class RTGCategoryModel extends \RetargetingSDK\Category
{
    /**
     * RTGCategoryModel constructor.
     * @param $categoryId
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    public function __construct($categoryId)
    {
        $this->setCategoryData($categoryId);
    }

    /**
     * @param $categoryId
     * @throws \RetargetingSDK\Exceptions\RTGException
     */
    private function setCategoryData($categoryId)
    {
        $langId = RTGConfigHelper::getParamValue('defaultLanguage');
        $category = new Category($categoryId, $langId);

        if (Validate::isLoadedObject($category)) {
            $this->setId($category->id);
            $this->setName($category->name);
            $this->setUrl(RTGLinkHelper::getCategoryLink($categoryId));

            if (!empty($category->id_parent)) {
                $breadcrumbs = [];

                $parentsCategories = $category->getParentsCategories($langId);

                foreach ($parentsCategories as $pCategoryIdx => $pCategory) {
                    if (isset($pCategory['id_category'])
                        && is_string($pCategory['name'])
                        && (int)$pCategory['active'] === 1
                        && $pCategory['id_category'] != $categoryId
                        && $pCategory['is_root_category'] < 1
                    ) {
                        $parentId = $pCategory['id_parent'];

                        if (!empty($parentsCategories[$pCategoryIdx + 1])
                            && $parentsCategories[$pCategoryIdx + 1]['is_root_category'] > 0
                        ) {
                            $parentId = false;
                        }

                        $breadcrumbs[] = [
                            'id'     => $pCategory['id_category'],
                            'name'   => $pCategory['name'],
                            'parent' => $parentId
                        ];
                    }
                }

                if (!empty($breadcrumbs)) {
                    $this->setParent($category->id_parent);
                    $this->setBreadcrumb($breadcrumbs);
                }
            }
        } else {
            throw new \RetargetingSDK\Exceptions\RTGException('Fail to load category with id: ' . $categoryId);
        }
    }
}
