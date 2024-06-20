<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage;
use Mage_Customer_Model_Address;
use Mage_Customer_Model_Customer;
use Mage_Customer_Model_Resource_Customer_Collection;
use Mage_Directory_Model_Resource_Country_Collection;
use Mage_Directory_Model_Resource_Region_Collection;
use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractCustomerCommand
 *
 * @package N98\Magento\Command\Customer
 */
abstract class AbstractCustomerCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomerModel(): Mage_Customer_Model_Customer
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getCustomerCollection(): Mage_Customer_Model_Resource_Customer_Collection
    {
        return Mage::getResourceModel('customer/customer_collection');
    }

    /**
     * @return Mage_Customer_Model_Address
     */
    protected function getAddressModel(): Mage_Customer_Model_Address
    {
        return Mage::getModel('customer/address');
    }

    /**
     * @return Mage_Directory_Model_Resource_Region_Collection
     */
    protected function getRegionCollection(): Mage_Directory_Model_Resource_Region_Collection
    {
        return Mage::getResourceModel('directory/region_collection');
    }

    /**
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    protected function getCountryCollection(): Mage_Directory_Model_Resource_Country_Collection
    {
        return Mage::getResourceModel('directory/country_collection');
    }
}
