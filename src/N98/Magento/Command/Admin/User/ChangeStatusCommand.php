<?php

namespace N98\Magento\Command\Admin\User;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeStatusCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:change-status')
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->addOption('activate', null, InputOption::VALUE_NONE, 'Activate user')
            ->addOption('deactivate', null, InputOption::VALUE_NONE, 'Deactivate user')
            ->setDescription('Set active status of an adminhtml user. If no option is set the status will be toggled.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            // Username

            $id = $this->getOrAskForArgument('id', $input, $output, 'Username or Email');
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
                    throw new RuntimeException(implode(PHP_EOL, $result));
                }

                if ($input->getOption('activate')) {
                    $user->setIsActive(1);
                }

                if ($input->getOption('deactivate')) {
                    $user->setIsActive(0);
                }

                // toggle is_active
                if (!$input->getOption('activate') && !$input->getOption('deactivate')) {
                    $user->setIsActive(!$user->getIsActive()); // toggle
                }

                $user->save();

                if ($user->getIsActive() == 1) {
                    $output->writeln(
                        '<info>User <comment>' . $user->getUsername() . '</comment>' .
                        ' is now <comment>active</comment></info>'
                    );
                } else {
                    $output->writeln(
                        '<info>User <comment>' . $user->getUsername() . '</comment>' .
                        ' is now <comment>inactive</comment></info>'
                    );
                }
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }
}
