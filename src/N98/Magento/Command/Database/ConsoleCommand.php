<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:console')
            ->setAliases(array('mysql-client'))
            ->addOption('use-mycli-instead-of-mysql', null, InputOption::VALUE_NONE, 'Use `mycli` as the MySQL client instead of `mysql`')
            ->setDescription('Opens mysql client by database config from local.xml');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $descriptorSpec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        );

        $mysqlClient = $input->getOption('use-mycli-instead-of-mysql') ? 'mycli' : 'mysql';
        $exec = $mysqlClient . ' ' . $this->getHelper('database')->getMysqlClientToolConnectionString();

        $pipes = array();
        $process = proc_open($exec, $descriptorSpec, $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }
    }
}
