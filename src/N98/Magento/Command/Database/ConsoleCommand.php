<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractDatabaseCommand
{
    public const COMMAND_OPTION_USE_MYCLI_INSTEAD_OF_MYSQL = 'use-mycli-instead-of-mysql';

    public const COMMAND_OPTION_NO_AUTO_REHASH = 'no-auto-rehash';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'db:console';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Opens mysql client by database config from local.xml.';

    protected function configure(): void
    {
        $this
            ->setAliases(['mysql-client'])
            ->addOption(
                self::COMMAND_OPTION_USE_MYCLI_INSTEAD_OF_MYSQL,
                null,
                InputOption::VALUE_NONE,
                'Use `mycli` as the MySQL client instead of `mysql`'
            )
            ->addOption(
                self::COMMAND_OPTION_NO_AUTO_REHASH,
                null,
                InputOption::VALUE_NONE,
                'Same as `-A` option to MySQL client to turn off ' .
                'auto-complete (avoids long initial connection time).'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectDbSettings($output);

        $args = [$input->getOption(self::COMMAND_OPTION_USE_MYCLI_INSTEAD_OF_MYSQL) ? 'mycli' : 'mysql'];

        if ($input->getOption(self::COMMAND_OPTION_NO_AUTO_REHASH)) {
            $args[] = '--no-auto-rehash';
        }

        $args[] = $this->getMysqlClientToolConnection();

        $this->processCommand(implode(' ', $args));

        return Command::SUCCESS;
    }

    /**
     * execute a command
     *
     * @param string $command
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

    /**
     * @return string
     */
    private function getMysqlClientToolConnection(): string
    {
        return $this->getDatabaseHelper()->getMysqlClientToolConnectionString();
    }
}
