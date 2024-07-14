<?php

namespace N98\Magento\Command\Database;

use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DropCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:drop')
            ->addOption('tables', 't', InputOption::VALUE_NONE, 'Drop all tables instead of dropping the database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Drop current database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
The command prompts before dropping the database. If --force option is specified it
directly drops the database.
The configured user in app/etc/local.xml must have "DROP" privileges.
HELP;
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

        /* @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');
        /** @var DatabaseHelper $dbHelper */
        $dbHelper = $this->getHelper('database');

        if ($input->getOption('force')) {
            $shouldDrop = true;
        } else {
            $shouldDrop = $dialog->ask(
                $input,
                $output,
                new ConfirmationQuestion('<question>Really drop database ' . $this->dbSettings['dbname'] .
                    ' ?</question> <comment>[n]</comment>: ', false)
            );
        }

        if ($shouldDrop) {
            if ($input->getOption('tables')) {
                $dbHelper->dropTables($output);
            } else {
                $dbHelper->dropDatabase($output);
            }
        }
        return 0;
    }
}
