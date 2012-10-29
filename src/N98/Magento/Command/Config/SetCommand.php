<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addArgument('value', InputArgument::REQUIRED, 'The config value')
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
            $config = \Mage::getModel('core/config');
            $config->saveConfig($input->getArgument('path'), $input->getArgument('value'));
            $output->writeln($input->getArgument('path') . " => " . $input->getArgument('value'));
        }
    }
}