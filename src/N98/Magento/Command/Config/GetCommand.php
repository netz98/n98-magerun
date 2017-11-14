<?php

namespace N98\Magento\Command\Config;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a core config item')
            ->setHelp(
                <<<EOT
                If <info>path</info> is not set, all available config items will be listed.
The <info>path</info> may contain wildcards (*).
If <info>path</info> ends with a trailing slash, all child items will be listed. E.g.

    config:get web/
is the same as
    config:get web/*
EOT
            )
            ->addArgument('path', InputArgument::OPTIONAL, 'The config path')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED,
                'The config value\'s scope (default, websites, stores)'
            )
            ->addOption('scope-id', null, InputOption::VALUE_REQUIRED, 'The config value\'s scope ID')
            ->addOption(
                'decrypt',
                null,
                InputOption::VALUE_NONE,
                'Decrypt the config value using local.xml\'s crypt key'
            )
            ->addOption('update-script', null, InputOption::VALUE_NONE, 'Output as update script lines')
            ->addOption('magerun-script', null, InputOption::VALUE_NONE, 'Output for usage with config:set')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );

        $help = <<<HELP
If path is not set, all available config items will be listed. path may contain wildcards (*)
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        /* @var $collection \Mage_Core_Model_Resource_Db_Collection_Abstract */
        $collection = $this->_getConfigDataModel()->getCollection();

        $searchPath = $input->getArgument('path');

        if (substr($input->getArgument('path'), -1, 1) === '/') {
            $searchPath .= '*';
        }

        $collection->addFieldToFilter('path', array(
            'like' => str_replace('*', '%', $searchPath),
        ));

        if ($scope = $input->getOption('scope')) {
            $collection->addFieldToFilter('scope', array('eq' => $scope));
        }

        if ($scopeId = $input->getOption('scope-id')) {
            $collection->addFieldToFilter(
                'scope_id',
                array('eq' => $scopeId)
            );
        }

        $collection->addOrder('path', 'ASC');

        // sort according to the config overwrite order
        // trick to force order default -> (f)website -> store , because f comes after d and before s
        $collection->addOrder('REPLACE(scope, "website", "fwebsite")', 'ASC');

        $collection->addOrder('scope_id', 'ASC');

        if ($collection->count() == 0) {
            $output->writeln(sprintf("Couldn't find a config value for \"%s\"", $input->getArgument('path')));

            return;
        }

        foreach ($collection as $item) {
            $table[] = array(
                'path'     => $item->getPath(),
                'scope'    => $item->getScope(),
                'scope_id' => $item->getScopeId(),
                'value'    => $this->_formatValue(
                    $item->getValue(),
                    $input->getOption('decrypt') ? 'decrypt' : false
                ),
            );
        }

        ksort($table);

        if ($input->getOption('update-script')) {
            $this->renderAsUpdateScript($output, $table);
        } elseif ($input->getOption('magerun-script')) {
            $this->renderAsMagerunScript($output, $table);
        } else {
            $this->renderAsTable($output, $table, $input->getOption('format'));
        }
    }

    /**
     * @param OutputInterface $output
     * @param array $table
     * @param string $format
     */
    protected function renderAsTable(OutputInterface $output, $table, $format)
    {
        $formattedTable = array();
        foreach ($table as $row) {
            $formattedTable[] = array(
                $row['path'],
                $row['scope'],
                $row['scope_id'],
                $this->renderTableValue($row['value'], $format),
            );
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('Path', 'Scope', 'Scope-ID', 'Value'))
            ->setRows($formattedTable)
            ->renderByFormat($output, $formattedTable, $format);
    }

    private function renderTableValue($value, $format)
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
                    throw new \UnexpectedValueException(
                        sprintf("Unhandled format %s", var_export($value, true))
                    );
            }
        }

        return $value;
    }

    /**
     * @param OutputInterface $output
     * @param array $table
     */
    protected function renderAsUpdateScript(OutputInterface $output, $table)
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
     * @param array $table
     */
    protected function renderAsMagerunScript(OutputInterface $output, $table)
    {
        foreach ($table as $row) {
            $value = $row['value'];
            if ($value !== null) {
                $value = str_replace(array("\n", "\r"), array('\n', '\r'), $value);
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
