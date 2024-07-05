<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Core;

use Mage;
use Mage_Core_Model_Resource;
use RuntimeException;

/**
 * Class Resource
 *
 * @package N98\Magento\Methods\Core
 */
class Resource
{
    /**
     * Get an instance of cms/block
     *
     * @return Mage_Core_Model_Resource
     */
    public static function getModel(): Mage_Core_Model_Resource
    {
        $model = Mage::getModel('core/resource');
        if (!$model instanceof Mage_Core_Model_Resource) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
