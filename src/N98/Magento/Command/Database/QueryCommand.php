<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Util\Exec;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Query database command
 *
 * @package N98\Magento\Command\Database
 */
class QueryCommand extends AbstractDatabaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('db:query')
            ->addArgument('query', InputArgument::OPTIONAL, 'SQL query')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysql command. Do not execute')
            ->setDescription('Executes an SQL query on the database defined in local.xml')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Executes an SQL query on the current configured database. Wrap your SQL in
single or double quotes.

If your query produces a result (e.g. a SELECT statement), the output of the
mysql cli tool will be returned.

* Requires MySQL CLI tools installed on your system.
HELP;
    }

    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    /**
     * Returns the query string with escaped ' characters so it can be used
     * within the mysql -e argument.
     *
     * The -e argument is enclosed by single quotes. As you can't escape
     * the single quote within the single quote, you have to end the quote,
     * then escape the single quote character and reopen the quote.
     */
    protected function getEscapedSql(string $query): string
    {
        return str_replace("'", "'\''", $query);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectDbSettings($output);

        $query = $this->getOrAskForArgument('query', $input, $output, 'SQL Query');

        $databaseHelper = $this->getDatabaseHelper();
        $exec = sprintf('mysql %s -e %s', $databaseHelper->getMysqlClientToolConnectionString(), escapeshellarg($query));

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
        } else {
            Exec::run($exec, $commandOutput, $returnValue);
            $output->writeln($commandOutput);
            if ($returnValue > 0) {
                $output->writeln('<error>' . $commandOutput . '</error>');
            }
        }

        return Command::SUCCESS;
    }
}
