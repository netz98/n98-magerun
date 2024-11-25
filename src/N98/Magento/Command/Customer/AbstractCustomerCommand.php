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
    protected function getCustomerModel(): Mage_Customer_Model_Customer
    {
        /** @var Mage_Customer_Model_Customer $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('customer/customer');
        return $mageCoreModelAbstract;
    }

    protected function getCustomerCollection(): Mage_Customer_Model_Resource_Customer_Collection
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection $mageCoreModelResourceDbCollectionAbstract */
        $mageCoreModelResourceDbCollectionAbstract = Mage::getResourceModel('customer/customer_collection');
        return $mageCoreModelResourceDbCollectionAbstract;
    }

    protected function getAddressModel(): Mage_Customer_Model_Address
    {
        /** @var Mage_Customer_Model_Address $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('customer/address');
        return $mageCoreModelAbstract;
    }

    protected function getRegionCollection(): Mage_Directory_Model_Resource_Region_Collection
    {
        /** @var Mage_Directory_Model_Resource_Region_Collection $mageCoreModelAbstract */
        $mageCoreModelAbstract = Mage::getModel('directory/region_collection');
        return $mageCoreModelAbstract;
    }

    protected function getCountryCollection(): Mage_Directory_Model_Resource_Country_Collection
    {
        /** @var Mage_Directory_Model_Resource_Country_Collection $mageCoreModelAbstract */
        $mageCoreModelAbstract = Mage::getModel('directory/country_collection');
        return $mageCoreModelAbstract;
    }
}
