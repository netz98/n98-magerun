<?php

namespace N98\Magento\Command\Customer;

use N98\Magento\Command\AbstractMagentoCommand;

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
     * @return \Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getCustomerCollection()
    {
        return $this->_getResourceModel('customer/customer_collection', 'Mage_Customer_Model_Resource_Customer_Collection');
    }
}
