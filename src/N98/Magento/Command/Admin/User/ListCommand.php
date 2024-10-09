<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;

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
    protected static $defaultName = 'admin:user:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List admin users.';

    /**
     * @var string
     */
    protected static string $noResultMessage = 'No admin users found.';

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
        if (is_array($this->data)) {
            return $this->data;
        }

        $userModel = $this->getUserModel();
        $userList = $userModel->getCollection();
        $this->data = [];
        /** @var Mage_Admin_Model_User $user */
        foreach ($userList as $user) {
            $this->data[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getIsActive() ? 'active' : 'inactive'
            ];
        }

        return $this->data;
    }
}
