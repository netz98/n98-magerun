<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create database command
 *
 * @package N98\Magento\Command\Database
 */
class CreateCommand extends AbstractDatabaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('db:create')
            ->setDescription('Create currently configured database')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
The command tries to create the configured database according to your
settings in app/etc/local.xml.
The configured user must have "CREATE DATABASE" privileges on MySQL Server.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getDatabaseHelper()->createDatabase($output);
        return Command::SUCCESS;
    }
}
