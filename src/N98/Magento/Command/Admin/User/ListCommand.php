<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List admin user password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ListCommand extends AbstractAdminUserCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'admin:user:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'List admin users.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Admin users';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'username', 'email', 'status'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        /** @var Mage_Admin_Model_User $userModel */
        $userModel = $this->getUserModel();
        $userList = $userModel->getCollection();
        $table = [];
        foreach ($userList as $user) {
            $table[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getIsActive() ? 'active' : 'inactive'
            ];
        }

        return $table;
    }
}
