<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractDatabaseCommand
{

    protected $tableDefinitions = null;

    protected function configure()
    {

        $this
            ->setName('db:dump')
            ->setAliases(array('database:dump'))
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption('add-time', null, InputOption::VALUE_NONE, 'Adds time to filename (only if filename was not provided)')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysqldump command. Do not execute')
            ->addOption('no-single-transaction', null, InputOption::VALUE_NONE, 'Do not use single-transaction (not recommended, this is blocking)')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addOption('strip', null, InputOption::VALUE_OPTIONAL, 'Tables to strip (dump only structure of those tables)')
            ->addDeprecatedAlias('database:dump', 'Please use db:dump')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml');
    }

    public function getTableDefinitions()
    {
        $this->commandConfig = $this->getCommandConfig();

        if(is_null($this->tableDefinitions)) {
            $this->tableDefinitions = array();
            if (isset($this->commandConfig['table-groups'])) {
                $tableGroups = $this->commandConfig['table-groups'];
                foreach($tableGroups as $index=>$definition) {
                    $description = isset($definition['description']) ? $definition['description'] : '';
                    if (!isset($definition['id'])) {
                        throw new \Exception('Invalid definition of table-groups (id missing) Index: '.$index);
                    }
                    if (!isset($definition['id'])) {
                        throw new \Exception('Invalid definition of table-groups (tables missing) Id: '.$definition['id']);
                    }

                    $this->tableDefinitions[$definition['id']] = array(
                        'tables' => $definition['tables'],
                        'description' => $description,
                    );
                }
            };
        }

        return $this->tableDefinitions;
    }

    /**
     * Generate help for table defintions
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
        $messages[] = '<comment>Available Table Groups</comment>';

        $definitions = $this->getTableDefinitions();
        foreach($definitions as $id=>$definition) {
            $description = isset($definition['description']) ? $definition['description'] : '';
            /** @TODO:
             * Column-Wise formating of the options, see InputDefinition::asText for code to pad by the max length,
             * but I do not like to copy and paste ..
             */
            $messages[] = ' <info>@'.$id.'</info> ' . $description;
        }
        return implode("\n", $messages);

    }

    public function asText() {
        return parent::asText() . "\n" .
            $this->getTableDefinitionHelp();
    }

    /**
     * @param $param
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     */
    protected function resolveTables($excludes, $resolved = array())
    {
        $definitions = $this->getTableDefinitions();

        $resolvedExcludes = array();
        foreach($excludes as $exclude) {
            if (substr($exclude, 0, 1) == '@') {
                $code = substr($exclude, 1);
                if (!isset($definitions[$code])) {
                    throw new \Exception('Table-groups could not be resolved: '.$exclude);
                }
                if (!isset($resolved[$code])) {
                    $resolved[$code] = true;
                    $tables = $this->resolveTables(explode(' ', $definitions[$code]['tables']), $resolved);
                    $resolvedExcludes = array_merge($resolvedExcludes, $tables);
                }
                continue;
            }

            // resolve wildcards
            if (strpos($exclude, '*')) {
                $connection = $this->_getConnection();
                $sth = $connection->prepare('SHOW TABLES LIKE :like', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                $sth->execute(
                    array(':like' => str_replace('*', '%', $exclude))
                );
                $rows = $sth->fetchAll();
                foreach($rows as $row) {
                    $resolvedExcludes[] = $row[0];
                }
                continue;
            }

            $resolvedExcludes[] = $exclude;
        }

        return $resolvedExcludes;
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        if (!$input->getOption('stdout')) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        $timeStamp = '_' . date('Y-m-d_His');
        if (($fileName = $input->getArgument('filename')) === null && !$input->getOption('stdout')) {
            $dialog = $this->getHelperSet()->get('dialog');
            $defaultName = $this->dbSettings['dbname']
                         . ($input->getOption('add-time') ? $timeStamp : '')
                         . '.sql';
            $fileName = $dialog->ask($output, '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]', $defaultName);
        } else {
            if (($input->getOption('add-time'))) {
                $extension_pos = strrpos($fileName, '.'); // find position of the last dot, so where the extension starts
                if ($extension_pos !== false) {
                    $fileName = substr($fileName, 0, $extension_pos) . $timeStamp . substr($fileName, $extension_pos);
                } else {
                    $fileName .= $timeStamp;
                }
            }
        }

        if (substr($fileName, -4, 4) !== '.sql') {
            $fileName .= '.sql';
        }

        if ($input->getOption('strip')) {
            $stripTables = $this->resolveTables(explode(' ', $input->getOption('strip')));
            if (!$input->getOption('stdout')) {
                $output->writeln('<comment>No-data export for: <info>' . implode(' ',$stripTables) . '</info></comment>');
            }
        } else {
            $stripTables = false;
        }


        if ($input->getOption('no-single-transaction')) {
            $dumpOptions = '--single-transaction ';
        } else {
            $dumpOptions = '';
        }

        $execs = array();


        if (!$stripTables) {
            $exec = 'mysqldump ' . $dumpOptions . $this->getMysqlClientToolConnectionString();
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        } else {
            // dump structure for strip-tables
            $exec = 'mysqldump ' . $dumpOptions . '--no-data ' . $this->getMysqlClientToolConnectionString();
            $exec .= ' ' . implode(' ', $stripTables);
            if (!$input->getOption('stdout')) {
                $exec .= ' > ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;

            $ignore = '';
            foreach($stripTables as $stripTable) {
                $ignore .= '--ignore-table=' . $this->dbSettings['dbname'] . '.' . $stripTable . ' ';
            }

            // dump data for all other tables
            $exec = 'mysqldump ' . $dumpOptions . $ignore . $this->getMysqlClientToolConnectionString();
            if (!$input->getOption('stdout')) {
                $exec .= ' >> ' . escapeshellarg($fileName);
            }
            $execs[] = $exec;
        }

        if ($input->getOption('only-command')) {
            foreach($execs as $exec) {
                $output->writeln($exec);
            }
        } else {
            if (!$input->getOption('stdout')) {
                $output->writeln('<comment>Start dumping database: <info>' . $this->dbSettings['dbname'] . '</info> to file <info>' . $fileName . '</info>');
            }

            foreach($execs as $exec) {
                if ($input->getOption('stdout')) {
                    passthru($exec, $returnValue);
                } else {
                    exec($exec, $commandOutput, $returnValue);
                }
                if ($returnValue > 0) {
                    $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
                }
            }
            if (!$input->getOption('stdout')) {
                $output->writeln('<info>Finished</info>');
            }
        }
    }

}