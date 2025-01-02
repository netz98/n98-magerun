<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Delete admin user password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class DeleteUserCommand extends AbstractAdminUserCommand
{
    protected function configure(): void
    {
        $this
            ->setName('admin:user:delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Delete the account of a adminhtml user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $questionHelper = $this->getQuestionHelper();

        // Username
        $id = $this->getOrAskForArgument('id', $input, $output, 'Username or Email');

        $user = $this->getUserModel()->loadByUsername($id);
        if (!$user->getId()) {
            $user = $this->getUserModel()->load($id, 'email');
        }

        if (!$user->getId()) {
            $output->writeln('<error>User was not found</error>');
            return Command::FAILURE;
        }

        $shouldRemove = $input->getOption('force');
        if (!$shouldRemove) {
            $shouldRemove = $questionHelper->ask(
                $input,
                $output,
                new ConfirmationQuestion('<question>Are you sure?</question> <comment>[n]</comment>: ', false),
            );
        }

        if ($shouldRemove) {
            try {
                $user->delete();
                $output->writeln('<info>User was successfully deleted</info>');
            } catch (Exception $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }
        } else {
            $output->writeln('<error>Aborting delete</error>');
        }

        return Command::SUCCESS;
    }
}
