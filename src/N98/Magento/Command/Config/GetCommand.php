<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use Mage_Core_Exception;
use Mage_Core_Model_Config_Data;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * Config get command
 *
 * @package N98\Magento\Command\Config
 */
class GetCommand extends AbstractConfigCommand
{
    public const COMMAND_ARGUMENT_PATH = 'path';

    public const COMMAND_OPTION_SCOPE = 'scope';

    public const COMMAND_OPTION_SCOPE_ID = 'scope-id';

    public const COMMAND_OPTION_DECRYPT = 'decrypt';

    public const COMMAND_OPTION_UPDATE_SCRIPT = 'update-script';

    public const COMMAND_OPTION_MAGERUN_SCRIPT = 'magerun-script';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'config:get';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Get a core config item.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_PATH,
                InputArgument::REQUIRED,
                'The config path'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE,
                null,
                InputOption::VALUE_REQUIRED,
                'The config value\'s scope (default, websites, stores)'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE_ID,
                null, InputOption::VALUE_REQUIRED,
                'The config value\'s scope ID'
            )
            ->addOption(
                self::COMMAND_OPTION_DECRYPT,
                null,
                InputOption::VALUE_NONE,
                'Decrypt the config value using local.xml\'s crypt key'
            )
            ->addOption(
                self::COMMAND_OPTION_UPDATE_SCRIPT,
                null,
                InputOption::VALUE_NONE,
                'Output as update script lines'
            )
            ->addOption(
                self::COMMAND_OPTION_MAGERUN_SCRIPT,
                null,
                InputOption::VALUE_NONE,
                'Output for usage with config:set'
            )
            ->addOption(
                self::COMMAND_OPTION_FORMAT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<EOT
If <info>path</info> is not set, all available config items will be listed.
The <info>path</info> may contain wildcards (*).
If <info>path</info> ends with a trailing slash, all child items will be listed. E.g.

    config:get web/
is the same as
    config:get web/*
EOT;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];
        $this->detectMagento($output);
        $this->initMagento();

        $collection = $this->_getConfigDataModel()->getCollection();

        /** @var string $searchPath */
        $searchPath = $input->getArgument(self::COMMAND_ARGUMENT_PATH);

        if (substr($searchPath, -1, 1) === '/') {
            $searchPath .= '*';
        }

        $collection->addFieldToFilter('path', ['like' => str_replace('*', '%', $searchPath)]);

        if ($scope = $input->getOption(self::COMMAND_OPTION_SCOPE)) {
            $collection->addFieldToFilter('scope', ['eq' => $scope]);
        }

        if ($scopeId = $input->getOption(self::COMMAND_OPTION_SCOPE_ID)) {
            $collection->addFieldToFilter('scope_id', ['eq' => $scopeId]);
        }

        $collection->addOrder('path', 'ASC');
        // sort according to the config overwrite order
        // trick to force order default -> (f)website -> store , because f comes after d and before s
        $collection->addOrder('REPLACE(scope, "website", "fwebsite")', 'ASC');
        $collection->addOrder('scope_id', 'ASC');

        if (!$collection->getSize()) {
            /** @var string $path */
            $path = $input->getArgument(self::COMMAND_ARGUMENT_PATH);
            $output->writeln(sprintf("Couldn't find a config value for \"%s\"", $path));

            return Command::SUCCESS;
        }

        /** @var Mage_Core_Model_Config_Data $item */
        foreach ($collection as $item) {
            $table[] = [
                'path'     => $item->getPath(),
                'scope'    => $item->getScope(),
                'scope_id' => $item->getScopeId(),
                'value'    => $this->_formatValue(
                    $item->getValue(),
                    $input->getOption(self::COMMAND_OPTION_DECRYPT) ? 'decrypt' : false
                )
            ];
        }

        ksort($table);

        if ($input->getOption(self::COMMAND_OPTION_UPDATE_SCRIPT)) {
            $this->renderAsUpdateScript($output, $table);
        } elseif ($input->getOption(self::COMMAND_OPTION_MAGERUN_SCRIPT)) {
            $this->renderAsMagerunScript($output, $table);
        } else {
            /** @var string|null $format */
            $format = $input->getOption(self::COMMAND_OPTION_FORMAT);
            $this->renderAsTable($output, $table, $format);
        }

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param array<int, array{path: string, scope: string, scope_id: int, value: string|null}> $table
     * @param string|null $format
     */
    protected function renderAsTable(OutputInterface $output, array $table, ?string $format): void
    {
        $formattedTable = [];
        foreach ($table as $row) {
            $formattedTable[] = [
                $row['path'],
                $row['scope'],
                $row['scope_id'],
                $this->renderTableValue($row['value'], $format)
            ];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Path', 'Scope', 'Scope-ID', 'Value'])
            ->setRows($formattedTable)
            ->renderByFormat($output, $formattedTable, $format);
    }

    /**
     * @param string|null $value
     * @param string|null $format
     * @return string|null
     */
    private function renderTableValue(?string $value, ?string$format): ?string
    {
        if ($value === null) {
            switch ($format) {
                case null:
                    $value = self::DISPLAY_NULL_UNKNOWN_VALUE;
                    break;
                case 'json':
                    break;
                case 'csv':
                case 'xml':
                    $value = 'NULL';
                    break;
                default:
                    throw new UnexpectedValueException(
                        sprintf("Unhandled format %s", var_export($value, true))
                    );
            }
        }

        return $value;
    }

    /**
     * @param OutputInterface $output
     * @param array<int, array{path: string, scope: string, scope_id: int, value: string|null}> $table
     */
    protected function renderAsUpdateScript(OutputInterface $output, array $table): void
    {
        $output->writeln('<?php');
        $output->writeln('$installer = $this;');
        $output->writeln('# generated by n98-magerun');

        foreach ($table as $row) {
            if ($row['scope'] == 'default') {
                $output->writeln(
                    sprintf(
                        '$installer->setConfigData(%s, %s);',
                        var_export($row['path'], true),
                        var_export($row['value'], true)
                    )
                );
            } else {
                $output->writeln(
                    sprintf(
                        '$installer->setConfigData(%s, %s, %s, %s);',
                        var_export($row['path'], true),
                        var_export($row['value'], true),
                        var_export($row['scope'], true),
                        var_export($row['scope_id'], true)
                    )
                );
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param array<int, array{path: string, scope: string, scope_id: int, value: string|null}> $table
     */
    protected function renderAsMagerunScript(OutputInterface $output, array $table): void
    {
        foreach ($table as $row) {
            $value = $row['value'];
            if ($value !== null) {
                $value = str_replace(["\n", "\r"], ['\n', '\r'], $value);
            }

            $disaplayValue = $value === null ? "NULL" : escapeshellarg($value);
            $protectNullString = $value === "NULL" ? '--no-null ' : '';

            $line = sprintf(
                'config:set %s--scope-id=%s --scope=%s -- %s %s',
                $protectNullString,
                $row['scope_id'],
                $row['scope'],
                escapeshellarg($row['path']),
                $disaplayValue
            );
            $output->writeln($line);
        }
    }
}
