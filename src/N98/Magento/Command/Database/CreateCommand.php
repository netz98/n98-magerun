<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractDatabaseCommand
{
    protected function configure(): void
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getHelper('database')->createDatabase($output);
        return 0;
    }
}
