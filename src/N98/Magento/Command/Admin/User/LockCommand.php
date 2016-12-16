<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LockCommand extends AbstractAdminUserCommand
{
    /**
     * The number of days to lock for (by default)
     * @var int
     */
    const LIFETIME_DEFAULT = 31;

    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('admin:user:lock')
            ->addArgument('username', InputArgument::REQUIRED, 'Admin username to lock')
            ->addArgument('lifetime', InputArgument::OPTIONAL, 'Optional - lock lifetime in days (default one month)')
            ->setDescription(
                <<<HELP
Enforce a lock on an admin user account. Specify the username and an optional lifetime parameter in seconds.
HELP
            );
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

        $username = $input->getArgument('username');
        $lifetime = $input->getArgument('lifetime') ?: $this->daysToSeconds(self::LIFETIME_DEFAULT);

        $user = \Mage::getModel('admin/user')->loadByUsername($username);
        if (!$user || !$user->getId()) {
            $output->writeln("<error>Couldn't find admin username '{$username}'</error>");
            return;
        }

        \Mage::getResourceModel('enterprise_pci/admin_user')->lock($user->getId(), 0, $lifetime);

        $lifetimeMessage = '';
        if ($input->getArgument('lifetime')) {
            $lifetimeMessage = sprintf(' for %d days.', $input->getArgument('lifetime'));
        }

        $output->writeln(
            sprintf('<info><comment>%s</comment> locked%s</info>', $username, $lifetimeMessage)
        );
    }

    /**
     * Convert a number of days to seconds for the lock lifetime parameter
     *
     * @param  int $days
     * @return int       Seconds
     */
    public function daysToSeconds($days)
    {
        return $days * 24 * 60 * 60;
    }
}
