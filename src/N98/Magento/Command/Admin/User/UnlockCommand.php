<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

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
            ->addArgument('username', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Admin Username to Unlock')
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
*@return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
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
            $dialog = $this->getHelperSet()->get('dialog');
            $shouldUnlockAll = $dialog->askConfirmation($output, '<question>Really unlock all users?</question> <comment>[n]</comment>: ', false);
            if ($shouldUnlockAll) {
                $ids = \Mage::getModel('admin/user')->getCollection()->getAllIds();
                if (count($ids) > 0) {
                    \Mage::getResourceModel('enterprise_pci/admin_user')->unlock($ids);
                }
                $output->writeln('<info><comment>All admins</comment> unlocked</info>');
            }
        }
    }
}
