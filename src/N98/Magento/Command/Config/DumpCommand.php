<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use DOMDocument;
use InvalidArgumentException;
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
    public const COMMAND_ARGUMENT_XPATH = 'xpath';

    /**
     * @var string
     */
    protected static $defaultName = 'config:dump';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Dump merged XML config.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_XPATH,
                InputArgument::OPTIONAL,
                'XPath to filter XML output'
            )
        ;
    }

    /**
     * @return string
     */
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $xpath */
        $xpath = $input->getArgument(self::COMMAND_ARGUMENT_XPATH);
        $config = $this->_getMageConfig()->getNode($xpath);
        if (!$config) {
            throw new InvalidArgumentException('xpath was not found');
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML((string)$config->asXML());
        $output->writeln((string)$dom->saveXML(), OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
