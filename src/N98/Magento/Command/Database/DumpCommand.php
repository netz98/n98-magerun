<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\Database\Compressor\Compressor;
use N98\Util\Console\Enabler;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\Exec;
use N98\Util\VerifyOrDie;
use RuntimeException;
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
            ->addOption(
                'add-time',
                't',
                InputOption::VALUE_OPTIONAL,
                'Adds time to filename (only if filename was not provided)'
            )
            ->addOption(
                'compression',
                'c',
                InputOption::VALUE_REQUIRED,
                'Compress the dump file using one of the supported algorithms'
            )
            ->addOption(
                'xml',
                null,
                InputOption::VALUE_NONE,
                'Dump database in xml format'
            )
            ->addOption(
                'hex-blob',
                null,
                InputOption::VALUE_NONE,
                'Dump binary columns using hexadecimal notation (for example, "abc" becomes 0x616263)'
            )
            ->addOption(
                'only-command',
                null,
                InputOption::VALUE_NONE,
                'Print only mysqldump command. Do not execute'
            )
            ->addOption(
                'print-only-filename',
                null,
                InputOption::VALUE_NONE,
                'Execute and prints no output except the dump filename'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'do everything but the dump')
            ->addOption(
                'no-single-transaction',
                null,
                InputOption::VALUE_NONE,
                'Do not use single-transaction (not recommended, this is blocking)'
            )
            ->addOption(
                'human-readable',
                null,
                InputOption::VALUE_NONE,
                'Use a single insert with column names per row. Useful to track database differences. Use db:import ' .
                '--optimize for speeding up the import.'
            )
            ->addOption(
                'add-routines',
                null,
                InputOption::VALUE_NONE,
                'Include stored routines in dump (procedures & functions)'
            )
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addOption(
                'strip',
                's',
                InputOption::VALUE_OPTIONAL,
                'Tables to strip (dump only structure of those tables)'
            )
            ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL, 'Tables to exclude from the dump')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not prompt if all options are defined')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml');

        $help = <<<HELP
Dumps configured magento database with `mysqldump`. You must have installed
the MySQL client tools.

On debian systems run `apt-get install mysql-client` to do that.

The command reads app/etc/local.xml to find the correct settings.

See it in action: http://youtu.be/ttjZHY6vThs

- If you like to prepend a timestamp to the dump name the --add-time option
  can be used.

- The command comes with a compression function. Add i.e. `--compression=gz`
  to dump directly in gzip compressed file.

HELP;
        $this->setHelp($help);
    }

    /**
     * @return array
     *
     * @deprecated Use database helper
     * @throws RuntimeException
     */
    public function getTableDefinitions()
    {
        $this->commandConfig = $this->getCommandConfig();

        if (is_null($this->tableDefinitions)) {
            /* @var $dbHelper DatabaseHelper */
            $dbHelper = $this->getHelper('database');

            $this->tableDefinitions = $dbHelper->getTableDefinitions($this->commandConfig);
        }

        return $this->tableDefinitions;
    }

    /**
     * Generate help for table definitions
     *
     * @return string
     */
    public function getTableDefinitionHelp()
    {
        $messages = PHP_EOL;
        $this->commandConfig = $this->getCommandConfig();
        $messages .= <<<HELP
<comment>Strip option</comment>
 If you like to skip data of some tables you can use the --strip option.
 The strip option creates only the structure of the defined tables and
 forces `mysqldump` to skip the data.

 Separate each table to strip by a space.
 You can use wildcards like * and ? in the table names to strip multiple
 tables. In addition you can specify pre-defined table groups, that start
 with an

 Example: "dataflow_batch_export unimportant_module_* @log

    $ n98-magerun.phar db:dump --strip="@stripped"

<comment>Available Table Groups</comment>

HELP;

        $definitions = $this->getTableDefinitions();
        $list = array();
        $maxNameLen = 0;
        foreach ($definitions as $id => $definition) {
            $name = '@' . $id;
            $description = isset($definition['description']) ? $definition['description'] . '.' : '';
            $nameLen = strlen($name);
            if ($nameLen > $maxNameLen) {
                $maxNameLen = $nameLen;
            }
            $list[] = array($name, $description);
        }

        $decrSize = 78 - $maxNameLen - 3;

        foreach ($list as $entry) {
            list($name, $description) = $entry;
            $delta = max(0, $maxNameLen - strlen($name));
            $spacer = $delta ? str_repeat(' ', $delta) : '';
            $buffer = wordwrap($description, $decrSize);
            $buffer = strtr($buffer, array("\n" => "\n" . str_repeat(' ', 3 + $maxNameLen)));
            $messages .= sprintf(" <info>%s</info>%s  %s\n", $name, $spacer, $buffer);
        }

        return $messages;
    }

    public function getHelp()
    {
        return
            parent::getHelp() . PHP_EOL
            . $this->getCompressionHelp() . PHP_EOL
            . $this->getTableDefinitionHelp();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // communicate early what is required for this command to run (is enabled)
        $enabler = new Enabler($this);
        $enabler->functionExists('exec');
        $enabler->functionExists('passthru');
        $enabler->operatingSystemIsNotWindows();

        $this->detectDbSettings($output);

        /* @var $dbHelper DatabaseHelper */
        $dbHelper = $this->getHelper('database');

        if (!$input->getOption('stdout') && !$input->getOption('only-command')
            && !$input->getOption('print-only-filename')
        ) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName = $this->getFileName($input, $output, $compressor);

        $stripTables = array();
        if ($input->getOption('strip')) {
            /* @var $database DatabaseHelper */
            $database = $dbHelper;
            $stripTables = $database->resolveTables(
                explode(' ', $input->getOption('strip')),
                $dbHelper->getTableDefinitions($this->getCommandConfig())
            );
            if (!$input->getOption('stdout') && !$input->getOption('only-command')
                && !$input->getOption('print-only-filename')
            ) {
                $output->writeln(
                    '<comment>No-data export for: <info>' . implode(' ', $stripTables) . '</info></comment>'
                );
            }
        }

        $excludeTables = array();
        if ($input->getOption('exclude')) {
            $excludeTables = $dbHelper->resolveTables(
                explode(' ', $input->getOption('exclude')),
                $dbHelper->getTableDefinitions($this->getCommandConfig())
            );
            if (!$input->getOption('stdout') && !$input->getOption('only-command')
                && !$input->getOption('print-only-filename')
            ) {
                $output->writeln(
                    '<comment>Excluded: <info>' . implode(' ', $excludeTables) . '</info></comment>'
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

        if ($input->getOption('xml')) {
            $dumpOptions .= '--xml ';
        }

        if ($input->getOption('hex-blob')) {
            $dumpOptions .= '--hex-blob ';
        }

        $execs = array();

        $ignore = '';
        foreach (array_merge($excludeTables, $stripTables) as $tableName) {
            $ignore .= '--ignore-table=' . $this->dbSettings['dbname'] . '.' . $tableName . ' ';
        }

        $mysqlClientToolConnectionString = $dbHelper->getMysqlClientToolConnectionString();

        if (count($stripTables) > 0) {
            // dump structure for strip-tables
            $exec = 'mysqldump ' . $dumpOptions . '--no-data ' . $mysqlClientToolConnectionString;
            $exec .= ' ' . implode(' ', $stripTables);
            $exec .= $this->postDumpPipeCommands();
            $exec = $compressor->getCompressingCommand($exec);
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        }

        // dump data for all other tables
        $exec = 'mysqldump ' . $dumpOptions . $mysqlClientToolConnectionString . ' ' . $ignore;
        $exec .= $this->postDumpPipeCommands();
        $exec = $compressor->getCompressingCommand($exec);
        if (!$input->getOption('stdout')) {
            $exec .= (count($stripTables) > 0 ? ' >> ' : ' > ') . escapeshellarg($fileName);
        }
        $execs[] = $exec;

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
                $output->writeln(
                    '<comment>Start dumping database <info>' . $this->dbSettings['dbname']
                    . '</info> to file <info>' . $fileName . '</info>'
                );
            }

            if ($input->getOption('dry-run')) {
                $execs = array();
            }

            foreach ($execs as $exec) {
                $commandOutput = '';
                if ($input->getOption('stdout')) {
                    passthru($exec, $returnValue);
                } else {
                    Exec::run($exec, $commandOutput, $returnValue);
                }
                if ($returnValue > 0) {
                    $output->writeln('<error>' . $commandOutput . '</error>');
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
        return ' | LANG=C LC_CTYPE=C LC_ALL=C sed -e ' . escapeshellarg('s/DEFINER[ ]*=[ ]*[^*]*\*/\*/');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Compressor $compressor
     *
     * @return string
     */
    protected function getFileName(InputInterface $input, OutputInterface $output, Compressor $compressor)
    {
        $namePrefix = '';
        $nameSuffix = '';
        if ($input->getOption('xml')) {
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

        if (
            (
                ($fileName = $input->getArgument('filename')) === null
                || ($isDir = is_dir($fileName))
            )
            && !$input->getOption('stdout')
        ) {
            /** @var DialogHelper $dialog */
            $dialog = $this->getHelper('dialog');
            $defaultName = VerifyOrDie::filename(
                $namePrefix . $this->dbSettings['dbname'] . $nameSuffix . $nameExtension
            );
            if (isset($isDir) && $isDir) {
                $defaultName = rtrim($fileName, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultName;
            }
            if (!$input->getOption('force')) {
                $fileName = $dialog->ask(
                    $output,
                    '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]',
                    $defaultName
                );
            } else {
                $fileName = $defaultName;
            }
        } else {
            if ($input->getOption('add-time')) {
                $pathParts = pathinfo($fileName);
                $fileName = ($pathParts['dirname'] == '.' ? '' : $pathParts['dirname'] . DIRECTORY_SEPARATOR) .
                    $namePrefix . $pathParts['filename'] . $nameSuffix . '.' . $pathParts['extension'];
            }
        }

        $fileName = $compressor->getFileName($fileName);

        return $fileName;
    }
}
