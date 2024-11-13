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
        /** @var Mage_Customer_Model_Customer $model */
        $model = $this->_getModel('customer/customer');
        return $model;
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getCustomerCollection()
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection $model */
        $model = $this->_getResourceModel('customer/customer_collection');
        return $model;
    }

    /**
     * @return Mage_Customer_Model_Address
     */
    protected function getAddressModel()
    {
        /** @var Mage_Customer_Model_Address $model */
        $model = $this->_getModel('customer/address');
        return $model;
    }

    /**
     * @return Mage_Directory_Model_Resource_Region_Collection
     */
    protected function getRegionCollection()
    {
        /** @var Mage_Directory_Model_Resource_Region_Collection $model */
        $model = $this->_getModel('directory/region_collection');
        return $model;
    }

    /**
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    protected function getCountryCollection()
    {
        /** @var Mage_Directory_Model_Resource_Country_Collection $model */
        $model = $this->_getModel('directory/country_collection');
        return $model;
    }
}
