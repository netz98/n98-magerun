<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage;
use Mage_Admin_Model_Roles;
use Mage_Admin_Model_Rules;
use Mage_Admin_Model_User;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractAdminUserCommand
 *
 * @package N98\Magento\Command\Admin\User
 */
abstract class AbstractAdminUserCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_ID = 'id';

    /**
     * @return Mage_Admin_Model_User
     */
    protected function getUserModel(): Mage_Admin_Model_User
    {
        return Mage::getModel('admin/user');
    }

    /**
     * @return Mage_Admin_Model_Roles
     */
    protected function getRoleModel(): Mage_Admin_Model_Roles
    {
        return Mage::getModel('admin/roles');
    }

    /**
     * @return Mage_Admin_Model_Rules
     */
    protected function getRulesModel(): Mage_Admin_Model_Rules
    {
        return Mage::getModel('admin/rules');
    }

    /**
     * Get User by ID or Email
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Mage_Admin_Model_User
     */
    protected function getUserByIdOrEmail(InputInterface $input, OutputInterface $output): Mage_Admin_Model_User
    {
        $identifier = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_ID, $input, $output, 'Username or Email');
        $user = $this->getUserModel()->loadByUsername($identifier);
        if (!$user->getId()) {
            $user = $this->getUserModel()->load($identifier, 'email');
        }

        return $user;
    }
}
