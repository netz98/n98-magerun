<?php

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:dump')
            ->addArgument('xpath', InputArgument::OPTIONAL, 'XPath to filter XML output', null)
            ->setDescription('Dump merged xml config')
        ;

        $help = <<<HELP
Dumps merged XML configuration to stdout. Useful to see all the XML.
You can filter the XML with first argument.

Examples:

  Config of catalog module

   $ n98-magerun.phar config:dump global/catalog

   See module order in XML

   $ n98-magerun.phar config:dump modules

   Write output to file

   $ n98-magerun.phar config:dump > extern_file.xml

HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $config = \Mage::app()->getConfig()->getNode($input->getArgument('xpath'));
        if (!$config) {
            throw new InvalidArgumentException('xpath was not found');
        }
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($config->asXml());
        $output->writeln($dom->saveXML(), OutputInterface::OUTPUT_RAW);
    }
}
