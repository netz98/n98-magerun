<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Catalog;

use Mage;
use Mage_Catalog_Model_Product;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Product
 *
 * @package N98\Magento\Methods\Catalog
 */
class Product implements ModelInterface
{
    /**
     * @return Mage_Catalog_Model_Product
     */
    public static function getModel(): Mage_Catalog_Model_Product
    {
        $model = Mage::getModel('catalog/product');
        if (!$model instanceof Mage_Catalog_Model_Product) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
