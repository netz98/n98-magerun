<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:info')
            ->setAliases(array('db:info'))
            ->setDescription('Dumps database informations')
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
        foreach ($this->dbSettings as $key => $value) {
            $output->writeln(str_pad($key, 25, ' ') . ': ' . $value);
        }

        $pdoConnectionString = sprintf(
            'mysql:host=%s;dbname=%s',
            $this->dbSettings['host'],
            $this->dbSettings['dbname']
        );
        $output->writeln(str_pad('PDO-Connection-String', 25, ' ') . ': ' . $pdoConnectionString);

        $jdbcConnectionString = sprintf(
            'jdbc:mysql://%s/%s?username=%s&password=%s',
            $this->dbSettings['host'],
            $this->dbSettings['dbname'],
            $this->dbSettings['username'],
            $this->dbSettings['password']
        );
        $output->writeln(str_pad('JDBC-Connection-String', 25, ' ') . ': ' . $jdbcConnectionString);

        $mysqlCliString = sprintf(
            'mysql -u%3$s -p%4$s -h%1$s %2$s',
            $this->dbSettings['host'],
            $this->dbSettings['dbname'],
            $this->dbSettings['username'],
            $this->dbSettings['password']
        );
        $output->writeln(str_pad('MySQL-Cli-String', 25, ' ') . ': ' . $mysqlCliString);
    }

}