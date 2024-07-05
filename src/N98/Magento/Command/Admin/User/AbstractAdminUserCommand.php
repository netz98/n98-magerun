<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Methods\Admin;
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
     * {@inheritDoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->detectMagento($output);
        $this->initMagento();
    }

    /**
     * Get User by ID or Email
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return Mage_Admin_Model_User
     *
     * @uses Admin\User::getModel()
     */
    protected function getUserByIdOrEmail(InputInterface $input, OutputInterface $output): Mage_Admin_Model_User
    {
        $identifier = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_ID, $input, $output, 'Username or Email');
        $user = Admin\User::getModel()->loadByUsername($identifier);
        if (!$user->getId()) {
            $user = Admin\User::getModel()->load($identifier, 'email');
        }

        return $user;
    }
}
