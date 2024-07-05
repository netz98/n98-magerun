<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Admin;

use Mage;
use Mage_Admin_Model_User;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class User
 *
 * @package N98\Magento\Methods\Admin
 */
class User implements ModelInterface
{
    /**
     * @return Mage_Admin_Model_User
     */
    public static function getModel(): Mage_Admin_Model_User
    {
        $model = Mage::getModel('admin/user');
        if (!$model instanceof Mage_Admin_Model_User) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
