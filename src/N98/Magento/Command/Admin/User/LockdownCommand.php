<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LockdownCommand extends LockCommand
{
    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('admin:user:lockdown')
            ->addArgument('lifetime', InputArgument::OPTIONAL, 'Optional - lock lifetime in days (default one month)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode')
            ->setDescription(
                <<<HELP
Lock every admin user account for the optionally specified lifetime (in days). If not provided, defaults to one month.
HELP
            );
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

        $lifetime = $input->getArgument('lifetime') ?: $this->daysToSeconds(self::LIFETIME_DEFAULT);

        $userIds = \Mage::getModel('admin/user')->getCollection()->getAllIds();

        if (empty($userIds)) {
            $output->writeln('<error>No admin users were found!</error>');
            return;
        }

        /** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog = $this->getHelper('dialog');
        $confirm = $dialog->askConfirmation(
            $output,
            sprintf('<question>Really lock all %d admin users?</question> <comment>[n]</comment>: ', count($userIds)),
            false
        );

        if (!$confirm) {
            return;
        }

        if (!$dryrun) {
            \Mage::getResourceModel('enterprise_pci/admin_user')->lock($userIds, 0, $lifetime);
        }

        $lifetimeMessage = '';
        if ($input->getArgument('lifetime')) {
            $lifetimeMessage = sprintf(' for %d days.', $input->getArgument('lifetime'));
        }

        $output->writeln(
            sprintf('<info><comment>All %d admins</comment> locked%s</info>', count($userIds), $lifetimeMessage)
        );
    }
}
