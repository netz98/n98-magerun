<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_User;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List admin command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ListCommand extends AbstractAdminUserCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Admin users';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:user:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'List admin users.';

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $userModel = $this->getUserModel();
            $userList = $userModel->getCollection();
            /** @var Mage_Admin_Model_User $user */
            foreach ($userList as $user) {
                $this->data[] = [
                    'id'        => $user->getId(),
                    'username'  => $user->getUsername(),
                    'email'     => $user->getEmail(),
                    'status'    => $user->getIsActive() ? 'active' : 'inactive'
                ];
            }
        }

        return $this->data;
    }
}
