<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Admin;

use Mage;
use Mage_Admin_Model_Rules;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Rules
 *
 * @package N98\Magento\Methods\Admin
 */
class Rules implements ModelInterface
{
    /**
     * @return Mage_Admin_Model_Rules
     */
    public static function getModel(): Mage_Admin_Model_Rules
    {
        $model = Mage::getModel('admin/rules');
        if (!$model instanceof Mage_Admin_Model_Rules) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
