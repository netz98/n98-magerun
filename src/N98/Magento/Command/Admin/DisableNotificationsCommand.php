<?php

namespace N98\Magento\Command\Admin;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DisableNotificationsCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:notifications')
            ->setDescription('Toggle admin notifications.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $enabled = \Mage::getStoreConfigFlag('advanced/modules_disable_output/Mage_AdminNotification');
            \Mage::app()->getConfig()->saveConfig('advanced/modules_disable_output/Mage_AdminNotification', $enabled ? 0 : 1, 'default');
            $output->writeln('<info>Admin nofitifactions <comment>' . (!$enabled ? 'enabled' : 'disabled') . '</comment></info>');
        }
    }
}