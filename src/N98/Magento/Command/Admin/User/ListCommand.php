<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List admin user password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ListCommand extends AbstractAdminUserCommand
{
    protected function configure(): void
    {
        $this
            ->setName('admin:user:list')
            ->setDescription('List admin users.')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $mageAdminModelUser = $this->getUserModel();
        $userList = $mageAdminModelUser->getCollection();
        $table = [];
        foreach ($userList as $user) {
            $table[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getIsActive() ? 'active' : 'inactive',
            ];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['id', 'username', 'email', 'status'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
