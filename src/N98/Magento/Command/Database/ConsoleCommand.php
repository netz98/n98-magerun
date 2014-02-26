<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:console')
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

        $exec = 'mysql ' . $this->getHelper('database')->getMysqlClientToolConnectionString();

        $pipes = array();
        $process = proc_open($exec, $descriptorSpec, $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }
    }
}