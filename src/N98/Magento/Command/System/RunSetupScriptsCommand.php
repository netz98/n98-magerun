<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunSetupScriptsCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('system:run-setup-scripts')
            ->setDescription('Runs all new setup scripts.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            \Mage_Core_Model_Resource_Setup::applyUpdates();
            if (is_callable(array('\Mage_Core_Model_Resource_Setup', 'applyAllDataUpdates'))) {
                \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
            }
            $output->writeln('<info>done</info>');
        }
    }
}
