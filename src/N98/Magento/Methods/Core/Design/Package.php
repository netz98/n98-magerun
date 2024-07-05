<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Core\Design;

use Mage;
use Mage_Core_Model_Design_Package;
use RuntimeException;

/**
 * Class Block
 *
 * @package N98\Magento\Methods\Core\Design
 */
class Package
{
    /**
     * Get an instance of core/design_package
     *
     * @return Mage_Core_Model_Design_Package
     */
    public static function getModel(): Mage_Core_Model_Design_Package
    {
        $model = Mage::getModel('core/design_package');
        if (!$model instanceof Mage_Core_Model_Design_Package) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
