<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:create')
            ->setDescription('Create currently configured database')
        ;

        $help = <<<HELP
The command tries to create the configured database according to your
settings in app/etc/local.xml.
The configured user must have "CREATE DATABASE" privileges on MySQL Server.
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

        $db = $this->getHelper('database')->getConnection();
        $db->query('CREATE DATABASE IF NOT EXISTS `' . $this->dbSettings['dbname'] . '`');
        $output->writeln('<info>Created database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

}