<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractAdminUserCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getUserModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_User_Model_User');
        } else {
            return \Mage::getModel('admin/user');
        }
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getRoleModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_User_Model_Role');
        } else {
            return \Mage::getModel('admin/roles');
        }
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getRulesModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_User_Model_Rules');
        } else {
            return \Mage::getModel('admin/rules');
        }
    }
}
