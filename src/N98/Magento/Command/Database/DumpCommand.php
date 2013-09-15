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
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysqldump command. Do not execute')
            ->addOption('print-only-filename', null, InputOption::VALUE_NONE, 'Execute and prints no output except the dump filename')
            ->addOption('no-single-transaction', null, InputOption::VALUE_NONE, 'Do not use single-transaction (not recommended, this is blocking)')
            ->addOption('human-readable', null, InputOption::VALUE_NONE, 'Use a single insert with column names per row. Useful to track database differences, but significantly slows down a later import')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addOption('strip', 's', InputOption::VALUE_OPTIONAL, 'Tables to strip (dump only structure of those tables)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not prompt if all options are defined')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml');
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
        if ($input->getOption('no-single-transaction')) {
            $dumpOptions = '--single-transaction ';
        }

        if ($input->getOption('human-readable')) {
            $dumpOptions .= '--complete-insert --skip-extended-insert ';
        }
        $execs = array();

        if (!$stripTables) {
            $exec = 'mysqldump ' . $dumpOptions . $this->getMysqlClientToolConnectionString();
            $exec .= $this->postDumpPipeCommands();
            $exec = $compressor->getCompressingCommand($exec);
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        } else {
            // dump structure for strip-tables
            $exec = 'mysqldump ' . $dumpOptions . '--no-data ' . $this->getMysqlClientToolConnectionString();
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
            $exec = 'mysqldump ' . $dumpOptions . $ignore . $this->getMysqlClientToolConnectionString();
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

        $fileName = $compressor->getFileName($fileName);

        return $fileName;
    }
}
