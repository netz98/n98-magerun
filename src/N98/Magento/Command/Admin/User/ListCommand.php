<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List admin command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ListCommand extends AbstractAdminUserCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Admin users';

    /**
     * @var string
     */
    protected static $defaultName = 'admin:user:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List admin users.';

    /**
     * {@inheritDoc}
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        $userModel = $this->getUserModel();
        $userList = $userModel->getCollection();
        /** @var Mage_Admin_Model_User $user */
        foreach ($userList as $user) {
            $this->data[] = [
                'Id'        => $user->getId(),
                'Username'  => $user->getUsername(),
                'Email'     => $user->getEmail(),
                'Status'    => $user->getIsActive() ? 'active' : 'inactive'
            ];
        }
    }
}
