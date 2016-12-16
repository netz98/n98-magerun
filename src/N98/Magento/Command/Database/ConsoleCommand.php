<?php

namespace N98\Magento\Command\Database;

use N98\Util\Console\Helper\DatabaseHelper;
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
            ->addOption(
                'use-mycli-instead-of-mysql',
                null,
                InputOption::VALUE_NONE,
                'Use `mycli` as the MySQL client instead of `mysql`'
            )
            ->addOption(
                'no-auto-rehash',
                null,
                InputOption::VALUE_NONE,
                'Same as `-A` option to MySQL client to turn off ' .
                'auto-complete (avoids long initial connection time).'
            )
            ->setDescription('Opens mysql client by database config from local.xml');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $args = array(
            $input->getOption('use-mycli-instead-of-mysql') ? 'mycli' : 'mysql',
        );

        if ($input->getOption('no-auto-rehash')) {
            $args[] = '--no-auto-rehash';
        }

        $args[] = $this->getMysqlClientToolConnection();

        $this->processCommand(implode(' ', $args));
    }

    /**
     * execute a command
     *
     * @param string $command
     */
    private function processCommand($command)
    {
        $descriptorSpec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        );

        $pipes = array();
        $process = proc_open($command, $descriptorSpec, $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }
    }

    /**
     * @return string
     */
    private function getMysqlClientToolConnection()
    {
        /* @var $database DatabaseHelper */
        $database = $this->getHelper('database');

        return $database->getMysqlClientToolConnectionString();
    }
}
