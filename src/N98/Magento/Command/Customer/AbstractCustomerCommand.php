<?php

namespace N98\Magento\Command\Customer;

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
    protected function getCustomerModel()
    {
        return $this->_getModel('customer/customer', 'Mage_Customer_Model_Customer');
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getCustomerCollection()
    {
        return $this->_getResourceModel(
            'customer/customer_collection',
            'Mage_Customer_Model_Resource_Customer_Collection'
        );
    }

    /**
     * @return Mage_Customer_Model_Address
     */
    protected function getAddressModel()
    {
        return $this->_getModel('customer/address', 'Mage_Customer_Model_Address');
    }

    /**
     * @return Mage_Directory_Model_Resource_Region_Collection
     */
    protected function getRegionCollection()
    {
        return $this->_getResourceModel(
            'directory/region_collection',
            'Mage_Directory_Model_Resource_Region_Collection'
        );
    }

    /**
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    protected function getCountryCollection()
    {
        return $this->_getResourceModel(
            'directory/country_collection',
            'Mage_Directory_Model_Resource_Country_Collection'
        );
    }
}
