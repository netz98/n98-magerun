<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:drop')
            ->addOption('tables', 't', InputOption::VALUE_NONE, 'Drop all tables instead of dropping the database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Drop current database')
        ;

        $help = <<<HELP
The command prompts before dropping the database. If --force option is specified it
directly drops the database.
The configured user in app/etc/local.xml must have "DROP" privileges.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);
        $dialog = $this->getHelperSet()->get('dialog');

        if ($input->getOption('force')) {
            $shouldDrop = true;
        } else {
            $shouldDrop = $dialog->askConfirmation($output, '<question>Really drop database ' . $this->dbSettings['dbname'] . ' ?</question> <comment>[n]</comment>: ', false);
        }

        if ($shouldDrop) {
            $db = $this->getHelper('database')->getConnection(); /* @var $db \PDO */
            if ($input->getOption('tables')) {
                $result = $db->query("SHOW TABLES");
                $query = 'SET FOREIGN_KEY_CHECKS = 0; ';
                $count = 0;
                while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                    $query .= 'DROP TABLE IF EXISTS `'.$row[0].'`; ';
                    $count++;
                }
                $query .= 'SET FOREIGN_KEY_CHECKS = 1;';
                $db->query($query);
                $output->writeln('<info>Dropped database tables</info> <comment>' . $count . ' tables dropped</comment>');
            } else {
                $db->query("DROP DATABASE `" . $this->dbSettings['dbname'] . "`");
                $output->writeln('<info>Dropped database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
            }
        }
    }

}