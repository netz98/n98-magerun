<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Customer;

use Mage;
use Mage_Customer_Model_Customer;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Customer
 *
 * @package N98\Magento\Methods\Customer
 */
class Customer implements ModelInterface
{
    /**
     * @return Mage_Customer_Model_Customer
     */
    public static function getModel(): Mage_Customer_Model_Customer
    {
        $model = Mage::getModel('customer/customer');
        if (!$model instanceof Mage_Customer_Model_Customer) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
