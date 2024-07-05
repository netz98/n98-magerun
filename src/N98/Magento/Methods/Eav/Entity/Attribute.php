<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Eav\Entity;

use Mage;
use Mage_Eav_Model_Entity_Attribute;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Attribute
 *
 * @package N98\Magento\Methods\Eav\Entity
 */
class Attribute implements ModelInterface
{
    /**
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public static function getModel(): Mage_Eav_Model_Entity_Attribute
    {
        $model = Mage::getModel('eav/entity_attribute');
        if (!$model instanceof Mage_Eav_Model_Entity_Attribute) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
