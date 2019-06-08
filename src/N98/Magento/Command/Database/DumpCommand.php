<?php

namespace N98\Magento\Command\Database;

use InvalidArgumentException;
use N98\Magento\Command\Database\Compressor\Compressor;
use N98\Util\Console\Enabler;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\Exec;
use N98\Util\VerifyOrDie;
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
                'Append or prepend a timestamp to filename if a filename is provided. ' .
                'Possible values are "suffix", "prefix" or "no".'
            )
            ->addOption(
                'compression',
                'c',
                InputOption::VALUE_REQUIRED,
                'Compress the dump file using one of the supported algorithms'
            )
            ->addOption(
                'dump-option',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Option(s) to pass to mysqldump command. E.g. --dump-option="--set-gtid-purged=off"'
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
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'do everything but the dump'
            )
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
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Tables to exclude from the dump'
            )
            ->addOption(
                'include',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Tables to include in the dump'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Do not prompt if all options are defined'
            )
            ->addOption(
                'connection',
                'con',
                InputOption::VALUE_OPTIONAL,
                'Specify local.xml connection node, default to default_setup'
            )
            ->setDescription('Dumps database with mysqldump cli client');

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
     */
    private function getTableDefinitions()
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

        $messages .= <<<HELP

Extended: https://github.com/netz98/n98-magerun/wiki/Stripped-Database-Dumps
HELP;

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

        // TODO(tk): Merge the DatabaseHelper, detectDbSettings is within abstract database command base class
        $this->detectDbSettings($output, $input->getOption('connection'));

        if ($this->nonCommandOutput($input)) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        list($fileName, $execs) = $this->createExecsArray($input, $output);

        $success = $this->runExecs($execs, $fileName, $input, $output);

        return $success ? 0 : 1; // return with correct exec code
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    private function createExecsArray(InputInterface $input, OutputInterface $output)
    {
        $execs = array();

        $dumpOptions = '';
        if (!$input->getOption('no-single-transaction')) {
            $dumpOptions .= '--single-transaction --quick ';
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

        $options = $input->getOption('dump-option');
        if (count($options) > 0) {
            $dumpOptions .= implode(' ', $options) . ' ';
        }

        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName = $this->getFileName($input, $output, $compressor);

        /* @var $database DatabaseHelper */
        $database = $this->getDatabaseHelper();

        $mysqlClientToolConnectionString = $database->getMysqlClientToolConnectionString();

        $stripTables = $this->stripTables($input, $output);
        if ($stripTables) {
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

        $excludeTables = $this->excludeTables($input, $output);

        // dump data for all other tables
        $ignore = '';
        foreach (array_merge($excludeTables, $stripTables) as $ignoreTable) {
            $ignore .= '--ignore-table=' . $this->dbSettings['dbname'] . '.' . $ignoreTable . ' ';
        }
        $exec = 'mysqldump ' . $dumpOptions . $mysqlClientToolConnectionString . ' ' . $ignore;
        $exec .= $this->postDumpPipeCommands();
        $exec = $compressor->getCompressingCommand($exec);
        if (!$input->getOption('stdout')) {
            $exec .= (count($stripTables) > 0 ? ' >> ' : ' > ') . escapeshellarg($fileName);
        }
        $execs[] = $exec;
        return array($fileName, $execs);
    }

    /**
     * @param array $execs
     * @param string $fileName
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function runExecs(array $execs, $fileName, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('only-command') && !$input->getOption('print-only-filename')) {
            foreach ($execs as $command) {
                $output->writeln($command);
            }
        } else {
            if ($this->nonCommandOutput($input)) {
                $output->writeln(
                    '<comment>Start dumping database <info>' . $this->dbSettings['dbname'] .
                    '</info> to file <info>' . $fileName . '</info>'
                );
            }

            $commands = $input->getOption('dry-run') ? array() : $execs;

            foreach ($commands as $command) {
                if (!$this->runExec($command, $input, $output)) {
                    return false;
                }
            }

            if (!$input->getOption('stdout') && !$input->getOption('print-only-filename')) {
                $output->writeln('<info>Finished</info>');
            }
        }

        if ($input->getOption('print-only-filename')) {
            $output->writeln($fileName);
        }

        return true;
    }

    /**
     * @param string $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function runExec($command, InputInterface $input, OutputInterface $output)
    {
        $commandOutput = '';

        if ($input->getOption('stdout')) {
            passthru($command, $returnCode);
        } else {
            Exec::run($command, $commandOutput, $returnCode);
        }

        if ($returnCode > 0) {
            $output->writeln('<error>' . $commandOutput . '</error>');
            $output->writeln('<error>Return Code: ' . $returnCode . '. ABORTED.</error>');

            return false;
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    private function stripTables(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('strip')) {
            return array();
        }

        $stripTables = $this->resolveDatabaseTables($input->getOption('strip'));

        if ($this->nonCommandOutput($input)) {
            $output->writeln(
                sprintf('<comment>No-data export for: <info>%s</info></comment>', implode(' ', $stripTables))
            );
        }

        return $stripTables;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    private function excludeTables(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('exclude') && $input->getOption('include')) {
            throw new InvalidArgumentException('Cannot specify --include with --exclude');
        }

        if (!$input->getOption('exclude')) {
            $excludeTables = array();
        } else {
            $excludeTables = $this->resolveDatabaseTables($input->getOption('exclude'));

            if ($this->nonCommandOutput($input)) {
                $output->writeln(
                    sprintf('<comment>Excluded: <info>%s</info></comment>', implode(' ', $excludeTables))
                );
            }
        }

        if ($input->getOption('include')) {
            $includeTables = $this->resolveDatabaseTables($input->getOption('include'));
            $excludeTables = array_diff($this->getDatabaseHelper()->getTables(), $includeTables);
            if ($this->nonCommandOutput($input)) {
                $output->writeln(
                    sprintf('<comment>Included: <info>%s</info></comment>', implode(' ', $includeTables))
                );
            }
        }

        return $excludeTables;
    }

    /**
     * @param string $list space separated list of tables
     * @return array
     */
    private function resolveDatabaseTables($list)
    {
        $database = $this->getDatabaseHelper();

        return $database->resolveTables(
            explode(' ', $list),
            $database->getTableDefinitions($this->getCommandConfig())
        );
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
        if ($input->getOption('xml')) {
            $nameExtension = '.xml';
        } else {
            $nameExtension = '.sql';
        }

        $optionAddTime = $input->getOption('add-time');
        list($namePrefix, $nameSuffix) = $this->getFileNamePrefixSuffix($optionAddTime);

        if (
            (
                ($fileName = $input->getArgument('filename')) === null
                || ($isDir = is_dir($fileName))
            )
            && !$input->getOption('stdout')
        ) {
            $defaultName = VerifyOrDie::filename(
                $namePrefix . $this->dbSettings['dbname'] . $nameSuffix . $nameExtension
            );
            if (isset($isDir) && $isDir) {
                $defaultName = rtrim($fileName, '/') . '/' . $defaultName;
            }
            if (!$input->getOption('force')) {
                /** @var DialogHelper $dialog */
                $dialog = $this->getHelper('dialog');
                $fileName = $dialog->ask(
                    $output,
                    '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]',
                    $defaultName
                );
            } else {
                $fileName = $defaultName;
            }
        } else {
            if ($optionAddTime) {
                $pathParts = pathinfo($fileName);
                $fileName = ($pathParts['dirname'] == '.' ? '' : $pathParts['dirname'] . '/') .
                    $namePrefix . $pathParts['filename'] . $nameSuffix . '.' . $pathParts['extension'];
            }
        }

        $fileName = $compressor->getFileName($fileName);

        return $fileName;
    }

    /**
     * @param null|bool|string $optionAddTime [optional] true for default "suffix", other string values: "prefix", "no"
     * @return array
     */
    private function getFileNamePrefixSuffix($optionAddTime = null)
    {
        $namePrefix = '';
        $nameSuffix = '';
        if ($optionAddTime === null) {
            return array($namePrefix, $nameSuffix);
        }

        $timeStamp = date('Y-m-d_His');

        if (in_array($optionAddTime, array('suffix', true), true)) {
            $nameSuffix = '_' . $timeStamp;
        } elseif ($optionAddTime === 'prefix') {
            $namePrefix = $timeStamp . '_';
        } elseif ($optionAddTime !== 'no') {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid --add-time value %s, possible values are none (for) "suffix", "prefix" or "no"',
                    var_export($optionAddTime, true)
                )
            );
        }

        return array($namePrefix, $nameSuffix);
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function nonCommandOutput(InputInterface $input)
    {
        return
            !$input->getOption('stdout')
            && !$input->getOption('only-command')
            && !$input->getOption('print-only-filename');
    }
}
