<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:drop')
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
            $db = $this->getHelper('database')->getConnection();
            $db->query("DROP DATABASE `" . $this->dbSettings['dbname'] . "`");
            $output->writeln('<info>Dropped database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
        }
    }

}