<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {

        $this
            ->setName('db:dump')
            ->setAliases(array('database:dump'))
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption('add-time', null, InputOption::VALUE_NONE, 'Adds time to filename (only if filename was not provided)')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysqldump command. Do not execute')
            ->addArgument('strip', InputArgument::OPTIONAL, 'Tables to strip (dump only structure) <error>foo</error>')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Dump to stdout')
            ->addDeprecatedAlias('database:dump', 'Please use db:dump')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml')
        ;
    }


    public function getTableDefinitionHelp()
    {
        $messages = array();
        $this->commandConfig = $this->getCommandConfig();
        $messages[] = '';
        $messages[] = '<comment>Strip parameter</comment>';
        $messages[] = ' Separate each table to strip by a space.';
        $messages[] = ' You can use wildcards like * and ? in the table names to strip multiple tables.';
        $messages[] = ' In addition you can specify pre-defined table groups, that start with an @';
        $messages[] = ' Example: "dataflow_batch_export unimportant_module_* @log';
        $messages[] = '<comment>Available Table Groups</comment>';
        if (isset($this->commandConfig['table-groups'])) {
            $tableGroups = $this->commandConfig['table-groups'];
            foreach($tableGroups as $index=>$definition) {
                $description = isset($definition['description']) ? $definition['description'] : '';
                if (!isset($definition['id'])) {
                    throw new \Exception('Invalid definition of table-groups (id missing) Index: '.$index);
                }
                /** @TODO:
                 * Column-Wise formating of the options, see InputDefinition::asText for code to pad by the max length,
                 * but I do not like to copy and paste ..
                 */
                $messages[] = ' <info>@'.$definition['id'].'</info> ' . $description;
            }
        }
        return implode("\n", $messages);

    }
    public function asText() {
        return parent::asText() . "\n" .
            $this->getTableDefinitionHelp();
    }

    protected function getTableGroups()
    {


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

        if (($fileName = $input->getArgument('filename')) === null && !$input->getOption('stdout')) {
            $dialog = $this->getHelperSet()->get('dialog');
            $defaultName = $this->dbSettings['dbname']
                         . ($input->getOption('add-time') ? '_' . date('Ymdhis') : '')
                         . '.sql';
            $fileName = $dialog->ask($output, '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]', $defaultName);
        }

        if (substr($fileName, -4, 4) !== '.sql') {
            $fileName .= '.sql';
        }

        $exec = 'mysqldump ' . $this->getMysqlClientToolConnectionString();
        if (!$input->getOption('stdout')) {
            $exec .= ' > ' . escapeshellarg($fileName);
        }

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
        } else {
            if (!$input->getOption('stdout')) {
                $output->writeln('<comment>Start dumping database: <info>' . $this->dbSettings['dbname'] . '</info> to file <info>' . $fileName . '</info>');
            }
            if ($input->getOption('stdout')) {
                passthru($exec, $returnValue);
            } else {
                exec($exec, $commandOutput, $returnValue);
            }
            if ($returnValue > 0) {
                $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
            } else {
                if (!$input->getOption('stdout')) {
                    $output->writeln('<info>Finished</info>');
                }
            }
        }
    }

}