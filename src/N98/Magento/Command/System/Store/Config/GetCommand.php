<?php

namespace N98\Magento\Command\System\Store\Config;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractMagentoStoreConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:store:config:get')
            ->setDescription('Get a store config entry from core_config_data table')
            ->addArgument('path', InputArgument::REQUIRED, 'Config path')
            ->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $store = $this->_initStore($input, $output);
            $value = \Mage::getStoreConfig($input->getArgument('path'), $store);
            $output->writeln('<comment>' . $value . '</comment>');
        }
    }
}
