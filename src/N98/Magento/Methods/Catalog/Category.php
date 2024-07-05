<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Catalog;

use Mage;
use Mage_Catalog_Model_Category;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Category
 *
 * @package N98\Magento\Methods\Catalog
 */
class Category implements ModelInterface
{
    /**
     * @return Mage_Catalog_Model_Category
     */
    public static function getModel(): Mage_Catalog_Model_Category
    {
        $model = Mage::getModel('catalog/category');
        if (!$model instanceof Mage_Catalog_Model_Category) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
