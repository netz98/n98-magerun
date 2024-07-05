<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Customer;

use Mage;
use Mage_Customer_Model_Address;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Address
 *
 * @package N98\Magento\Methods\Customer
 */
class Address implements ModelInterface
{
    /**
     * @return Mage_Customer_Model_Address
     */
    public static function getModel(): Mage_Customer_Model_Address
    {
        $model = Mage::getModel('customer/address');
        if (!$model instanceof Mage_Customer_Model_Address) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
