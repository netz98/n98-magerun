<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_Roles;
use Mage_Admin_Model_Rules;
use Mage_Admin_Model_User;
use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractAdminUserCommand
 *
 * @package N98\Magento\Command\Admin\User
 */
abstract class AbstractAdminUserCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Admin_Model_User
     */
    protected function getUserModel()
    {
        /** @var Mage_Admin_Model_User $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('admin/user');
        return $mageCoreModelAbstract;
    }

    /**
     * @return Mage_Admin_Model_Roles
     */
    protected function getRoleModel()
    {
        /** @var Mage_Admin_Model_Roles $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('admin/roles');
        return $mageCoreModelAbstract;
    }

    /**
     * @return Mage_Admin_Model_Rules
     */
    protected function getRulesModel()
    {
        /** @var Mage_Admin_Model_Rules $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('admin/rules');
        return $mageCoreModelAbstract;
    }
}
