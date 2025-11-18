<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use DOMDocument;
use InvalidArgumentException;
use Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump config command
 *
 * @package N98\Magento\Command\Config
 */
class DumpCommand extends AbstractConfigCommand
{
    protected function configure(): void
    {
        $this
            ->setName('config:dump')
            ->addArgument('xpath', InputArgument::OPTIONAL, 'XPath to filter XML output')
            ->setDescription('Dump merged xml config')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
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
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $config = Mage::app()->getConfig()->getNode($input->getArgument('xpath'));
        if (!$config) {
            throw new InvalidArgumentException('xpath was not found');
        }

        $domDocument = new DOMDocument();
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        $configXml = $config->asXml();
        if (!$configXml) {
            return Command::FAILURE;
        }

        $domDocument->loadXML($configXml);
        $domDocumentXml = $domDocument->saveXML();
        if (!$domDocumentXml) {
            return Command::FAILURE;
        }

        $output->writeln($domDocumentXml, OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
