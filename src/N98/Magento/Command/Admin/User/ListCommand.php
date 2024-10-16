<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List admin user password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ListCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:list')
            ->setDescription('List admin users.')
            ->addFormatOption()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        /** @var \Mage_Admin_Model_User $userModel */
        $userModel = $this->getUserModel();
        $userList = $userModel->getCollection();
        $table = [];
        foreach ($userList as $user) {
            $table[] = [$user->getId(), $user->getUsername(), $user->getEmail(), $user->getIsActive() ? 'active' : 'inactive'];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['id', 'username', 'email', 'status'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
