<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractAdminUserCommand
 *
 * @package N98\Magento\Command\Admin\User
 */
abstract class AbstractAdminUserCommand extends AbstractMagentoCommand
{
    /**
     * @return \Mage_Core_Model_Abstract|\Mage_Admin_Model_User
     */
    protected function getUserModel()
    {
        return $this->_getModel('admin/user');
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function getRoleModel()
    {
        return $this->_getModel('admin/roles');
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function getRulesModel()
    {
        return $this->_getModel('admin/rules');
    }
}
