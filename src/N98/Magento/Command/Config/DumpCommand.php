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
            ->addArgument('xpath', InputArgument::OPTIONAL, 'XPath to filter XML output', null)
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
        if ($this->initMagento()) {
            $config = \Mage::app()->getConfig()->getNode($input->getArgument('xpath'));
            if (!$config) {
                throw new \InvalidArgumentException('xpath was not found');
            }
            $dom = \DOMDocument::loadXML($config->asXml());
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $output->writeln($dom->saveXML());
        }
    }
}