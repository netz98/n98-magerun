<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('config:dump')
            ->setDescription('Dump merged xml config')
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
        $this->initMagento();

        $dom = dom_import_simplexml(\Mage::app()->getConfig()->getNode())->ownerDocument;
        $dom->formatOutput = true;
        $output->writeln($dom->saveXML());
    }
}