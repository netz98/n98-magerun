<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePasswordCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:change-password')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->setDescription('Changes the password of a adminhtml user.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento($output)) {
            
            $dialog = $this->getHelperSet()->get('dialog');
            
            // Username
            if (($username = $input->getArgument('username')) == null) {
                $username = $dialog->ask($output, '<question>Username:</question>');
            }

            $user = $this->getUserModel()->loadByUsername($username);
            if ($user->getId() <= 0) {
                $output->writeln('<error>User was not found</error>');
                return;
            }

            // Password
            if (($password = $input->getArgument('password')) == null) {
                $password = $dialog->ask($output, '<question>Password:</question>');
            }

            try {
                $result = $user->validate();
                if (is_array($result)) {
                    throw new \Exception(implode(PHP_EOL, $result));
                }
                $user->setPassword($password);
                $user->save();
                $output->writeln('<info>Password successfully changed</info>');
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }
}