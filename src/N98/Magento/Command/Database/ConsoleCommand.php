<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:console')
            ->setAliases(array('mysql-client'))
            ->setDescription('Opens mysql client by database config from local.xml')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $descriptorSpec = array(
           0 => STDIN,
           1 => STDOUT,
           2 => STDERR
        );

        $exec = sprintf(
            'mysql -h%s -u%s -p%s %s',
            $this->dbSettings['host'],
            $this->dbSettings['username'],
            $this->dbSettings['password'],
            $this->dbSettings['dbname']
        );

        $pipes = array();
        proc_open($exec, $descriptorSpec, $pipes);
    }
}