<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractAdminUserCommand extends AbstractMagentoCommand
{
    /**
     * @return \Mage_Core_Model_Abstract|\Mage_Admin_Model_User
     */
    protected function getUserModel()
    {
        return $this->_getModel('admin/user', 'Mage_User_Model_User');
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function getRoleModel()
    {
        return $this->_getModel('admin/roles', 'Mage_User_Model_Role');
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function getRulesModel()
    {
        return $this->_getModel('admin/rules', 'Mage_User_Model_Rules');
    }
}
