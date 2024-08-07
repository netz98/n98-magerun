<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\CommandFormatable;

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
     * @return string
     */
    public function getSectionTitle(): string
    {
        return 'Admin users';
    }

    /**
     * @return string[]
     */
    public function getListHeader(): array
    {
        return ['id', 'username', 'email', 'status'];
    }

    /**
     * @return array
     */
    public function getListData(): array
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
