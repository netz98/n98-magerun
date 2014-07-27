<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class DeleteUserCommand
 */
class DeleteUserCommand extends AbstractAdminUserCommand
{
    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('admin:user:delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Delete the account of a adminhtml user.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $dialog = $this->getHelperSet()->get('dialog');
            // Username
            if (($id = $input->getArgument('id')) == null) {
                $id = $dialog->ask($output, '<question>Username or Email:</question>');
            }

            $user = $this->getUserModel()->loadByUsername($id);
            if (!$user->getId()) {
                $user = $this->getUserModel()->load($id, 'email');
            }

            if (!$user->getId()) {
                $output->writeln('<error>User was not found</error>');
                return;
            }

            $shouldRemove = $input->getOption('force');
            if (!$shouldRemove) {
                $shouldRemove = $dialog->askConfirmation($output, '<question>Are you sure?</question> <comment>[n]</comment>: ', false);
            }

            if ($shouldRemove) {
                try {
                    $user->delete();
                    $output->writeln('<info>User was successfully deleted</info>');
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            } else {
                $output->writeln('<error>Aborting delete</error>');
            }
        }
    }
}