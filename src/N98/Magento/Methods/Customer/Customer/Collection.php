<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Customer\Customer;

use Mage;
use Mage_Customer_Model_Resource_Customer_Collection;
use N98\Magento\Methods\ResourceModelInterface;
use RuntimeException;

/**
 * Class Collection
 *
 * @package N98\Magento\Methods\Customer\Customer
 */
class Collection implements ResourceModelInterface
{
    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    public static function getResourceModel(): Mage_Customer_Model_Resource_Customer_Collection
    {
        $model = Mage::getResourceModel('customer/customer_collection');
        if (!$model instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
