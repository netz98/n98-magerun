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
 * Database query command
 *
 * @package N98\Magento\Command\Database
 */
class QueryCommand extends AbstractDatabaseCommand
{
    public const COMMAND_ARGUMENT_QUERY = 'query';

    public const COMMAND_OPTION_ONLY_COMMAND = 'only-command';

    /**
     * @var string
     */
    protected static $defaultName = 'db:query';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Executes an SQL query on the database defined in local.xml.';

    protected static bool $detectMagentoFlag = false;

    protected static bool $initMagentoFlag = false;

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_QUERY,
                InputArgument::OPTIONAL,
                'SQL query'
            )
            ->addOption(
                self::COMMAND_OPTION_ONLY_COMMAND,
                null,
                InputOption::VALUE_NONE,
                'Print only mysql command. Do not execute'
            )
        ;
    }

    /**
     * @return string
     */
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

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    /**
     * Returns the query string with escaped ' characters, so it can be used
     * within the mysql -e argument.
     *
     * The -e argument is enclosed by single quotes. As you can't escape
     * the single quote within the single quote, you have to end the quote,
     * then escape the single quote character and reopen the quote.
     *
     * @param string $query
     * @return string
     */
    protected function getEscapedSql(string $query): string
    {
        return str_replace("'", "'\''", $query);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectDbSettings($output);

        $query = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_QUERY, $input, $output, 'SQL Query');
        $exec = $this->getQueryString($query);

        if ($input->getOption(self::COMMAND_OPTION_ONLY_COMMAND)) {
            $output->writeln($exec);
            return Command::SUCCESS;
        }

        Exec::run($exec, $commandOutput, $returnValue);
        if ($commandOutput) {
            $output->writeln($commandOutput);
            if ($returnValue > 0) {
                $output->writeln('<error>' . $commandOutput . '</error>');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $query
     * @return string
     */
    private function getQueryString(string $query): string
    {
        $helper = $this->getDatabaseHelper();
        return sprintf(
            'mysql %s -e %s',
            $helper->getMysqlClientToolConnectionString(),
            escapeshellarg($query)
        );
    }
}
