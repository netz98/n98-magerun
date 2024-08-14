<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage;
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
        return Mage::getModel('admin/user');
    }

    /**
     * @return Mage_Admin_Model_Roles
     */
    protected function getRoleModel()
    {
        return Mage::getModel('admin/roles');
    }

    /**
     * @return Mage_Admin_Model_Rules
     */
    protected function getRulesModel()
    {
        return Mage::getModel('admin/rules');
    }
}
