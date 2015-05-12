<?php

namespace N98\Magento\Command\Database;

use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractDatabaseCommand
{
    /**
     * @var array
     */
    protected $tableDefinitions = null;

    /**
     * @var array
     */
    protected $commandConfig = null;

    protected function configure()
    {
        $this
            ->setName('db:dump')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption('add-time', 't', InputOption::VALUE_OPTIONAL, 'Adds time to filename (only if filename was not provided)')
            ->addOption('compression', 'c', InputOption::VALUE_REQUIRED, 'Compress the dump file using one of the supported algorithms')
            ->addOption('xml', null, InputOption::VALUE_NONE, 'Dump database in xml format')
            ->addOption('hex-blob', null, InputOption::VALUE_NONE, 'Dump binary columns using hexadecimal notation (for example, "abc" becomes 0x616263)')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysqldump command. Do not execute')
            ->addOption('print-only-filename', null, InputOption::VALUE_NONE, 'Execute and prints no output except the dump filename')
            ->addOption('no-single-transaction', null, InputOption::VALUE_NONE, 'Do not use single-transaction (not recommended, this is blocking)')
            ->addOption('human-readable', null, InputOption::VALUE_NONE, 'Use a single insert with column names per row. Useful to track database differences. Use db:import --optimize for speeding up the import.')
            ->addOption('add-routines', null, InputOption::VALUE_NONE, 'Include stored routines in dump (procedures & functions)')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addOption('strip', 's', InputOption::VALUE_OPTIONAL, 'Tables to strip (dump only structure of those tables)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not prompt if all options are defined')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml');

        $help = <<<HELP
Dumps configured magento database with `mysqldump`.
You must have installed the MySQL client tools.

On debian systems run `apt-get install mysql-client` to do that.

The command reads app/etc/local.xml to find the correct settings.
If you like to skip data of some tables you can use the --strip option.
The strip option creates only the structure of the defined tables and
forces `mysqldump` to skip the data.

Dumps your database and excludes some tables. This is useful i.e. for development.

Separate each table to strip by a space.
You can use wildcards like * and ? in the table names to strip multiple tables.
In addition you can specify pre-defined table groups, that start with an @
Example: "dataflow_batch_export unimportant_module_* @log

   $ n98-magerun.phar db:dump --strip="@stripped"

Available Table Groups:

* @log Log tables
* @dataflowtemp Temporary tables of the dataflow import/export tool
* @importexporttemp Temporary tables of the Import/Export module
* @stripped Standard definition for a stripped dump (logs, dataflow and importexport)
* @sales Sales data (orders, invoices, creditmemos etc)
* @customers Customer data
* @trade Current trade data (customers and orders). You usally do not want those in developer systems.
* @development Removes logs and trade data so developers do not have to work with real customer data

Extended: https://github.com/netz98/n98-magerun/wiki/Stripped-Database-Dumps

See it in action: http://youtu.be/ttjZHY6vThs

- If you like to prepend a timestamp to the dump name the --add-time option can be used.

- The command comes with a compression function. Add i.e. `--compression=gz` to dump directly in
 gzip compressed file.

HELP;
        $this->setHelp($help);

    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return function_exists('exec') && !OperatingSystem::isWindows();
    }

    /**
     * @return array
     * @deprecated Use database helper
     * @throws \Exception
     */
    public function getTableDefinitions()
    {
        $this->commandConfig = $this->getCommandConfig();

        if (is_null($this->tableDefinitions)) {
            $this->tableDefinitions = array();
            if (isset($this->commandConfig['table-groups'])) {
                $tableGroups = $this->commandConfig['table-groups'];
                foreach ($tableGroups as $index=>$definition) {
                    $description = isset($definition['description']) ? $definition['description'] : '';
                    if (!isset($definition['id'])) {
                        throw new \Exception('Invalid definition of table-groups (id missing) Index: ' . $index);
                    }
                    if (!isset($definition['id'])) {
                        throw new \Exception('Invalid definition of table-groups (tables missing) Id: '
                            . $definition['id']
                        );
                    }

                    $this->tableDefinitions[$definition['id']] = array(
                        'tables'      => $definition['tables'],
                        'description' => $description,
                    );
                }
            };
        }

        return $this->tableDefinitions;
    }

    /**
     * Generate help for table definitions
     *
     * @return string
     * @throws \Exception
     */
    public function getTableDefinitionHelp()
    {
        $messages = array();
        $this->commandConfig = $this->getCommandConfig();
        $messages[] = '';
        $messages[] = '<comment>Strip option</comment>';
        $messages[] = ' Separate each table to strip by a space.';
        $messages[] = ' You can use wildcards like * and ? in the table names to strip multiple tables.';
        $messages[] = ' In addition you can specify pre-defined table groups, that start with an @';
        $messages[] = ' Example: "dataflow_batch_export unimportant_module_* @log';
        $messages[] = '';
        $messages[] = '<comment>Available Table Groups</comment>';

        $definitions = $this->getTableDefinitions();
        foreach ($definitions as $id => $definition) {
            $description = isset($definition['description']) ? $definition['description'] : '';
            /** @TODO:
             * Column-Wise formatting of the options, see InputDefinition::asText for code to pad by the max length,
             * but I do not like to copy and paste ..
             */
            $messages[] = ' <info>@' . $id . '</info> ' . $description;
        }

        return implode(PHP_EOL, $messages);
    }

    public function getHelp()
    {
        return parent::getHelp() . PHP_EOL
            . $this->getCompressionHelp() . PHP_EOL
            . $this->getTableDefinitionHelp();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        if (!$input->getOption('stdout') && !$input->getOption('only-command')
            && !$input->getOption('print-only-filename')
        ) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName   = $this->getFileName($input, $output, $compressor);

        $stripTables = false;
        if ($input->getOption('strip')) {
            $stripTables = $this->getHelper('database')->resolveTables(explode(' ', $input->getOption('strip')), $this->getTableDefinitions());
            if (!$input->getOption('stdout') && !$input->getOption('only-command')
                && !$input->getOption('print-only-filename')
            ) {
                $output->writeln('<comment>No-data export for: <info>' . implode(' ', $stripTables)
                    . '</info></comment>'
                );
            }
        }

        $dumpOptions = '';
        if (!$input->getOption('no-single-transaction')) {
            $dumpOptions = '--single-transaction --quick ';
        }

        if ($input->getOption('human-readable')) {
            $dumpOptions .= '--complete-insert --skip-extended-insert ';
        }

        if ($input->getOption('add-routines')) {
            $dumpOptions .= '--routines ';
        }

        if($input->getOption('xml')) {
            $dumpOptions .= '--xml ';
        }

        if($input->getOption('hex-blob')) {
            $dumpOptions .= '--hex-blob ';
        }

        $execs = array();

        if (!$stripTables) {
            $exec = 'mysqldump ' . $dumpOptions . $this->getHelper('database')->getMysqlClientToolConnectionString();
            $exec .= $this->postDumpPipeCommands();
            $exec = $compressor->getCompressingCommand($exec);
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        } else {
            // dump structure for strip-tables
            $exec = 'mysqldump ' . $dumpOptions . '--no-data ' . $this->getHelper('database')->getMysqlClientToolConnectionString();
            $exec .= ' ' . implode(' ', $stripTables);
            $exec .= $this->postDumpPipeCommands();
            $exec = $compressor->getCompressingCommand($exec);
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;

            $ignore = '';
            foreach ($stripTables as $stripTable) {
                $ignore .= '--ignore-table=' . $this->dbSettings['dbname'] . '.' . $stripTable . ' ';
            }

            // dump data for all other tables
            $exec = 'mysqldump ' . $dumpOptions . $ignore . $this->getHelper('database')->getMysqlClientToolConnectionString();
            $exec .= $this->postDumpPipeCommands();
            $exec = $compressor->getCompressingCommand($exec);
            if (!$input->getOption('stdout')) {
                $exec .= ' >> ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        }

        $this->runExecs($execs, $fileName, $input, $output);
    }

    /**
     * @param array $execs
     * @param string $fileName
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function runExecs(array $execs, $fileName, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('only-command') && !$input->getOption('print-only-filename')) {
            foreach ($execs as $exec) {
                $output->writeln($exec);
            }
        } else {
            if (!$input->getOption('stdout') && !$input->getOption('only-command')
                && !$input->getOption('print-only-filename')
            ) {
                $output->writeln('<comment>Start dumping database <info>' . $this->dbSettings['dbname']
                    . '</info> to file <info>' . $fileName . '</info>'
                );
            }

            foreach ($execs as $exec) {
                $commandOutput = '';
                if ($input->getOption('stdout')) {
                    passthru($exec, $returnValue);
                } else {
                    exec($exec, $commandOutput, $returnValue);
                }
                if ($returnValue > 0) {
                    $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
                    $output->writeln('<error>Return Code: ' . $returnValue . '. ABORTED.</error>');

                    return;
                }
            }

            if (!$input->getOption('stdout') && !$input->getOption('print-only-filename')) {
                $output->writeln('<info>Finished</info>');
            }
        }

        if ($input->getOption('print-only-filename')) {
            $output->writeln($fileName);
        }
    }

    /**
     * Commands which filter mysql data. Piped to mysqldump command
     *
     * @return string
     */
    protected function postDumpPipeCommands()
    {
        return ' | sed -e ' . escapeshellarg('s/DEFINER[ ]*=[ ]*[^*]*\*/\*/');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \N98\Magento\Command\Database\Compressor\AbstractCompressor $compressor
     * @return string
     */
    protected function getFileName(InputInterface $input, OutputInterface $output,
        Compressor\AbstractCompressor $compressor
    ) {
        $namePrefix    = '';
        $nameSuffix    = '';
        if($input->getOption('xml')) {
            $nameExtension = '.xml';
        } else {
            $nameExtension = '.sql';
        }


        if ($input->getOption('add-time') !== false) {
            $timeStamp = date('Y-m-d_His');

            if ($input->getOption('add-time') == 'suffix') {
                $nameSuffix = '_' . $timeStamp;
            } else {
                $namePrefix = $timeStamp . '_';
            }
        }

        if ((($fileName = $input->getArgument('filename')) === null || ($isDir = is_dir($fileName))) && !$input->getOption('stdout')) {
            /** @var DialogHelper $dialog */
            $dialog      = $this->getHelperSet()->get('dialog');
            $defaultName = $namePrefix . $this->dbSettings['dbname'] . $nameSuffix . $nameExtension;
            if (isset($isDir) && $isDir) $defaultName = rtrim($fileName, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultName;
            if (!$input->getOption('force')) {
                $fileName = $dialog->ask($output, '<question>Filename for SQL dump:</question> [<comment>'
                    . $defaultName . '</comment>]', $defaultName
                );
            } else {
                $fileName = $defaultName;
            }
        } else {
            if ($input->getOption('add-time')) {
                $pathParts = pathinfo($fileName);
                $fileName = ($pathParts['dirname'] == '.' ? '' : $pathParts['dirname'] . DIRECTORY_SEPARATOR ) .
                    $namePrefix . $pathParts['filename'] . $nameSuffix . '.' . $pathParts['extension'];
            }
        }

        $fileName = $compressor->getFileName($fileName);

        return $fileName;
    }
}
