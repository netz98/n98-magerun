<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create database command
 *
 * @package N98\Magento\Command\Database
 */
class CreateCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:create')
            ->setDescription('Create currently configured database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
The command tries to create the configured database according to your
settings in app/etc/local.xml.
The configured user must have "CREATE DATABASE" privileges on MySQL Server.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getDatabaseHelper()->createDatabase($output);
        return 0;
    }
}
