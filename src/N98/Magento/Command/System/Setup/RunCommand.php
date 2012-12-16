<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:run')
            ->setAliases(array('sys:run-setup-scripts', 'system:run-setup-scripts'))
            ->addDeprecatedAlias('sys:run-setup-scripts', 'Please use sys:setup:run')
            ->addDeprecatedAlias('system:run-setup-scripts', 'Please use sys:setup:run')
            ->setDescription('Runs all new setup scripts.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setAutoExit(false);
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $this->getApplication()->run(new StringInput('cache:flush'), new NullOutput());

            \Mage_Core_Model_Resource_Setup::applyAllUpdates();
            if (is_callable(array('\Mage_Core_Model_Resource_Setup', 'applyAllDataUpdates'))) {
                \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
            }
            $output->writeln('<info>done</info>');
        }
    }
}
