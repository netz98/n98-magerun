<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\Database\Compressor\AbstractCompressor;
use N98\Magento\Command\Database\Compressor\Uncompressed;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractDatabaseCommand
{
    const CSV_DATA_FOLDER_SUFFIX = '_csv_data';

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
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysqldump command. Do not execute')
            ->addOption('print-only-filename', null, InputOption::VALUE_NONE, 'Execute and prints no output except the dump filename')
            ->addOption('no-single-transaction', null, InputOption::VALUE_NONE, 'Do not use single-transaction (not recommended, this is blocking)')
            ->addOption('human-readable', null, InputOption::VALUE_NONE, 'Use a single insert with column names per row. Useful to track database differences, but significantly slows down a later import')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addOption('strip', 's', InputOption::VALUE_OPTIONAL, 'Tables to strip (dump only structure of those tables)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not prompt if all options are defined')
            ->addOption('data-to-csv', 'd', InputOption::VALUE_NONE, 'Dump table data to csv files.')
            ->setDescription('Dumps database with mysqldump cli client according to information from local.xml');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !OperatingSystem::isWindows();
    }

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

    /**
     * Generate help for data-to-csv option
     *
     * @return string
     */
    protected function getDataToCsvHelp()
    {
        $messages = array();
        $messages[] = '';
        $messages[] = '<comment>data-to-csv option</comment>';
        $messages[] = ' Decreases result archive size.';
        $messages[] = ' Speedups export by 10-20%, depending on db size.';
        $messages[] = ' Dramatically speedups import, depending on db size it can be 5,6 and even more times faster.';
        $messages[] = ' Requirements:';
        $messages[] = '     1. Must be used only on the same host where mysql server (mysqld) is running';
        $messages[] = '     2. Export to csv option needs mysqld running with tmpdir variables set (either in my.cnf or as runtime option --tmpdir)';
        $messages[] = '     3. mysql user must have FILE privilege';
        $messages[] = ' For more info see SELECT ... INTO documentation: http://dev.mysql.com/doc/refman/5.5/en/select-into.html';

        return implode(PHP_EOL, $messages);
    }

    public function getHelp()
    {
        return parent::getHelp() . PHP_EOL
            . $this->getCompressionHelp() . PHP_EOL
            . $this->getTableDefinitionHelp() . PHP_EOL
            . $this->getDataToCsvHelp();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $dataToCsv = false;
        if ($input->getOption('data-to-csv')) {
            $dataToCsv = true;
        }

        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName   = $this->getFileName($input, $output);

        if ($input->getOption('print-only-filename')) {
            $output->writeln($compressor->getFileName($fileName));

            return;
        }

        if (!$input->getOption('stdout') && !$input->getOption('only-command')) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        $stripTables = array();
        if ($input->getOption('strip')) {
            $stripTables = $this->resolveTables(explode(' ', $input->getOption('strip')), $this->getTableDefinitions());
            if (!$input->getOption('stdout') && !$input->getOption('only-command')) {
                $output->writeln('<comment>No-data export for: <info>' . implode(' ', $stripTables)
                    . '</info></comment>'
                );
            }
        }

        $dumpOptions = '';
        if ($input->getOption('no-single-transaction')) {
            $dumpOptions .= '--single-transaction ';
        }
        if ($input->getOption('human-readable')) {
            $dumpOptions .= '--complete-insert --skip-extended-insert ';
        }

        $execs = array();
        if ($dataToCsv) {
            $exec = $this->getExecToDumpDbStructureOnly($dumpOptions, $stripTables);
            $exec .= $this->postDumpPipeCommands();
            $exec .=  ' > ' . escapeshellarg($fileName);
            $execs[] = $exec;

            $folderName = $fileName . static::CSV_DATA_FOLDER_SUFFIX;
            $dataToCsvExecs = $this->getExecToDumpDbDataAsCsv($folderName, $stripTables);

            $execs = array_merge($execs,
                $this->prepareDataToCsvExecs($dataToCsvExecs, $fileName, $folderName, $compressor, $input)
            );
        } else {
            $exec = $this->getExecToDumpDbStructureAndDataAsSql($dumpOptions, $stripTables);
            $exec .= $this->postDumpPipeCommands();
            $execs[] = $this->prepareExec($exec, $compressor, $input, $fileName);
        }

        $this->runExecs($input, $output, $compressor, $fileName, $execs);
    }

    /**
     * Get mysqldump command to dump db structure
     *
     * @param string $dumpOptions
     * @param array $stripTables
     * @return string
     */
    protected function getExecToDumpDbStructureOnly($dumpOptions, array $stripTables)
    {
        $exec = 'mysqldump ' . $dumpOptions . '--no-data ' . $this->getMysqlClientToolConnectionString();
        $exec .= ' ' . implode(' ', $stripTables);

        return $exec;
    }

    /**
     * Get mysqldump command to dump db data
     *
     * @param string $dumpOptions
     * @param array $stripTables
     * @return string
     */
    protected function getExecToDumpDbStructureAndDataAsSql($dumpOptions, array $stripTables)
    {
        $ignore = '';
        foreach ($stripTables as $stripTable) {
            $ignore .= '--ignore-table=' . $this->dbSettings['dbname'] . '.' . $stripTable . ' ';
        }

        $exec = 'mysqldump ' . $dumpOptions . $ignore . $this->getMysqlClientToolConnectionString();

        return $exec;
    }

    /**
     * Get mysqldump command to dump db data
     *
     * @param string $folderName
     * @param array $stripTables
     * @return array
     * @throws \Exception
     */
    protected function getExecToDumpDbDataAsCsv($folderName, array $stripTables)
    {
        $mysqlTmpDir = $this->detectMysqlTmpDir();
        if (!$mysqlTmpDir) {
            throw new \Exception('No mysql tmpdir detected.');
        }
        if (!is_writable($mysqlTmpDir)) {
            throw new \Exception("Mysql tmpdir isn't writable for current user.");
        }

        if (!$this->mysqlUserHasPrivilege('FILE')) {
            throw new \Exception("Current mysql user doesn't have FILE privilege.");
        }

        $tables = $this->getTables();
        if (!$tables) {
            throw new \Exception("Can't get list of db tables.");
        }
        $tablesToDump = array_diff($tables, $stripTables);

        $tmpFolderName = rtrim($mysqlTmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $folderName
            . DIRECTORY_SEPARATOR;

        $execs = array(
            'rm -rf ' . $tmpFolderName,
            'mkdir -p ' . $tmpFolderName
        );
        $connectionString = $this->getMysqlClientToolConnectionString();
        foreach ($tablesToDump as $table) {
            $execs[] = <<<SQL
mysql {$connectionString} <<EOF
    SELECT * INTO OUTFILE '{$tmpFolderName}{$table}.data.csv'
        FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
        LINES TERMINATED BY '\\n'
    FROM {$table};
EOF
SQL;
        }

        $execs[] = 'mv ' . $tmpFolderName . ' .';

        return $execs;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Compressor\AbstractCompressor $compressor
     * @param string $fileName
     * @param array $execs
     */
    protected function runExecs(InputInterface $input, OutputInterface $output, AbstractCompressor $compressor,
        $fileName, array $execs
    )
    {
        if ($input->getOption('only-command')) {
            foreach ($execs as $exec) {
                $output->writeln($exec);
            }
        } else {
            if (!$input->getOption('stdout') && !$input->getOption('only-command')) {
                if ($input->getOption('data-to-csv')) {
                    if ($compressor instanceof Uncompressed) {
                        $output->writeln('<comment>Start dumping database <info>' . $this->dbSettings['dbname']
                            . '</info> to the file <info>' . $fileName . '</info></comment>'
                        );
                        $output->writeln('<comment>Data files in csv format will be saved to the folder <info>'
                            . $fileName . static::CSV_DATA_FOLDER_SUFFIX . '</info></comment>'
                        );
                    } else {
                        $output->writeln('<comment>Start dumping database <info>' . $this->dbSettings['dbname']
                            . '</info>'
                        );
                        $output->writeln('Both table structure sql dump and data files in csv format will be '
                            . 'saved to the file <info>' . $compressor->getFileName($fileName) . '</info></comment>'
                        );
                    }
                } else {
                    $output->writeln('<comment>Start dumping database <info>' . $this->dbSettings['dbname']
                        . '</info> to the file <info>' . $compressor->getFileName($fileName) . '</info></comment>'
                    );
                }
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

            if (!$input->getOption('stdout')) {
                $output->writeln('<info>Finished</info>');
            }
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @internal param \N98\Magento\Command\Database\Compressor\AbstractCompressor $compressor
     * @return string
     */
    protected function getFileName(InputInterface $input, OutputInterface $output)
    {
        $namePrefix    = '';
        $nameSuffix    = '';
        $nameExtension = '.sql';

        if ($input->getOption('add-time') !== false) {
            $timeStamp = date('Y-m-d_His');

            if ($input->getOption('add-time') == 'suffix') {
                $nameSuffix = '_' . $timeStamp;
            } else {
                $namePrefix = $timeStamp . '_';
            }
        }

        if (($fileName = $input->getArgument('filename')) === null && !$input->getOption('stdout')) {
            /** @var DialogHelper $dialog */
            $dialog      = $this->getHelperSet()->get('dialog');
            $defaultName = $namePrefix . $this->dbSettings['dbname'] . $nameSuffix . $nameExtension;
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

        return $fileName;
    }

    /**
     * Prepare mysqldump command according to user input, add post dump pipe commands
     *
     * @param string $exec
     * @param AbstractCompressor $compressor
     * @param InputInterface $input
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    protected function prepareExec($exec, AbstractCompressor $compressor, InputInterface $input, $fileName,
        $pipe = true
    )
    {
        $fileName = $compressor->getFileName($fileName, $pipe);
        if (!$pipe) {
            $exec = $fileName . ' ' . $exec;
        }
        $exec = $compressor->getCompressingCommand($exec, $pipe);
        if (!$input->getOption('stdout')) {
            if ($pipe) {
                $exec .=  ' > ' . escapeshellarg($fileName);
            }
        }

        return $exec;
    }

    /**
     * Generate exes needed to dump
     *
     * @param array $dataToCsvExecs
     * @param string $fileName
     * @param string $folderName
     * @param AbstractCompressor $compressor
     * @param InputInterface $input
     * @return array
     */
    private function prepareDataToCsvExecs(array $dataToCsvExecs, $fileName, $folderName,
        AbstractCompressor $compressor, InputInterface $input
    )
    {
        $execs = array('rm -rf ' . $folderName);
        $execs = array_merge($execs, $dataToCsvExecs);
        // ugly!!! :(
        if (!($compressor instanceof Uncompressed)) {
            $execs[] = $this->prepareExec($fileName . ' ' . $folderName, $compressor, $input, $fileName, false);
            $execs[] = 'rm -f ' . $fileName;
            $execs[] = 'rm -rf ' . $folderName;
        }

        return $execs;
    }
}
