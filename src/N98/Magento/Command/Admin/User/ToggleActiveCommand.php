<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ToggleActiveCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:toggle-active')
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->setDescription('Toggles active status of an adminhtml user.')
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

            // Username
            if (($id = $input->getArgument('id')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
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

            try {
                $result = $user->validate();
                if (is_array($result)) {
                    throw new \Exception(implode(PHP_EOL, $result));
                }

                // toggle is_active
                if ($user->getIsActive() == 1) {
                    $user->setIsActive(0);
                } else {
                    $user->setIsActive(1);
                }
                $user->save();

                if ($user->getIsActive() == 1) {
                    $output->writeln('<info>User <comment>' . $user->getUsername() . '</comment> is now <comment>active</comment></info>');
                } else {
                    $output->writeln('<info>User <comment>' . $user->getUsername() . '</comment> is now <comment>inactive</comment></info>');
                }

            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }
}