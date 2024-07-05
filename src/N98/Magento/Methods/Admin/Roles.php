<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Admin;

use Mage;
use Mage_Admin_Model_Roles;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Roles
 *
 * @package N98\Magento\Methods\Admin
 */
class Roles implements ModelInterface
{
    /**
     * @return Mage_Admin_Model_Roles
     */
    public static function getModel(): Mage_Admin_Model_Roles
    {
        $model = Mage::getModel('admin/roles');
        if (!$model instanceof Mage_Admin_Model_Roles) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
