<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockCommand extends AbstractAdminUserCommand
{
    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('admin:user:unlock')
            ->addArgument(
                'username',
                \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
                'Admin Username to Unlock'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode')
            ->setDescription('Release lock on admin user for one or all users');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        if ($dryrun = $input->getOption('dry-run')) {
            $output->writeln('<info>Dry run mode enabled.</info>');
        }

        // Unlock a single admin account
        if ($username = $input->getArgument('username')) {
            $user = \Mage::getModel('admin/user')->loadByUsername($username);
            if (!$user || !$user->getId()) {
                $output->writeln('<error>Couldn\'t find admin ' . $username . '</error>');
                return;
            }
            \Mage::getResourceModel('enterprise_pci/admin_user')->unlock($user->getId());
            $output->writeln('<info><comment>' . $username . '</comment> unlocked</info>');
            return;
        }

        // Unlock all admin accounts
        $userIds = \Mage::getModel('admin/user')->getCollection()->getAllIds();

        if (empty($userIds)) {
            $output->writeln('<error>No admin users found.</error>');
            return;
        }

        /** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog = $this->getHelper('dialog');
        $shouldUnlockAll = $dialog->askConfirmation(
            $output,
            sprintf(
                '<question>Really unlock all %d admin users?</question> <comment>[n]</comment>: ',
                count($userIds)
            ),
            false
        );

        if ($shouldUnlockAll) {
            if (!$dryrun) {
                \Mage::getResourceModel('enterprise_pci/admin_user')->unlock($userIds);
            }
            $output->writeln(
                sprintf('<info><comment>All %d admin users</comment> unlocked</info>', count($userIds))
            );
        }
    }
}
