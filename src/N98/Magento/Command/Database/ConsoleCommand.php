<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database console command
 *
 * @package N98\Magento\Command\Database
 */
class ConsoleCommand extends AbstractDatabaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('db:console')
            ->setAliases(['mysql-client'])
            ->addOption(
                'use-mycli-instead-of-mysql',
                null,
                InputOption::VALUE_NONE,
                'Use `mycli` as the MySQL client instead of `mysql`',
            )
            ->addOption(
                'no-auto-rehash',
                null,
                InputOption::VALUE_NONE,
                'Same as `-A` option to MySQL client to turn off ' .
                'auto-complete (avoids long initial connection time).',
            )
            ->setDescription('Opens mysql client by database config from local.xml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectDbSettings($output);

        $args = [$input->getOption('use-mycli-instead-of-mysql') ? 'mycli' : 'mysql'];

        if ($input->getOption('no-auto-rehash')) {
            $args[] = '--no-auto-rehash';
        }

        $args[] = $this->getMysqlClientToolConnection();

        $this->processCommand(implode(' ', $args));
        return Command::SUCCESS;
    }

    /**
     * Execute a command
     */
    private function processCommand(string $command): void
    {
        $descriptorSpec = [0 => STDIN, 1 => STDOUT, 2 => STDERR];

        $pipes = [];
        $process = proc_open($command, $descriptorSpec, $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }
    }

    private function getMysqlClientToolConnection(): string
    {
        $databaseHelper = $this->getDatabaseHelper();
        return $databaseHelper->getMysqlClientToolConnectionString();
    }
}
