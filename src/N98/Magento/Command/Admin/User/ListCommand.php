<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\Admin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_null;

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
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Id', 'Username', 'Email', 'Status'];
    }

    /**
     * {@inheritDoc}
     *
     * @uses Admin\User::getModel()
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];
            $userModel = Admin\User::getModel();
            $userList = $userModel->getCollection();
            /** @var Mage_Admin_Model_User $user */
            foreach ($userList as $user) {
                $this->data[] = [
                    $user->getId(),
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getIsActive() ? 'active' : 'inactive'
                ];
            }
        }

        return $this->data;
    }
}
