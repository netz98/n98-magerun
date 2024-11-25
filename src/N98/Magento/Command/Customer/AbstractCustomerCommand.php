<?php

declare(strict_types=1);

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
        /** @var Mage_Customer_Model_Customer $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('customer/customer');
        return $mageCoreModelAbstract;
    }

    /**
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getCustomerCollection()
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection $mageCoreModelResourceDbCollectionAbstract */
        $mageCoreModelResourceDbCollectionAbstract = $this->_getResourceModel('customer/customer_collection');
        return $mageCoreModelResourceDbCollectionAbstract;
    }

    /**
     * @return Mage_Customer_Model_Address
     */
    protected function getAddressModel()
    {
        /** @var Mage_Customer_Model_Address $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('customer/address');
        return $mageCoreModelAbstract;
    }

    /**
     * @return Mage_Directory_Model_Resource_Region_Collection
     */
    protected function getRegionCollection()
    {
        /** @var Mage_Directory_Model_Resource_Region_Collection $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('directory/region_collection');
        return $mageCoreModelAbstract;
    }

    /**
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    protected function getCountryCollection()
    {
        /** @var Mage_Directory_Model_Resource_Country_Collection $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('directory/country_collection');
        return $mageCoreModelAbstract;
    }
}
