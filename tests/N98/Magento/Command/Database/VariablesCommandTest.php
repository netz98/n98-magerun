<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Tester\CommandTester;

final class VariablesCommandTest extends TestCase
{
    /**
     * @var StatusCommand
     */
    private $statusCommand;

    /**
     * @return CommandTester
     */
    private function getCommand(array $options)
    {
        $this->statusCommand = new StatusCommand();

        $application = $this->getApplication();
        $application->add($this->statusCommand);

        $command = $this->getApplication()->find('db:variables');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName()], $options),
        );

        return $commandTester;
    }

    /**
     * @return DatabaseHelper
     */
    private function getDatabaseHelper()
    {
        return $this->statusCommand->getHelper('database');
    }

    public function testExecute()
    {
        $commandTester = $this->getCommand(['--format' => 'csv']);
        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('have_query_cache', $display);
        $this->assertStringContainsString('innodb_log_buffer_size', $display);
        $this->assertStringContainsString('max_connections', $display);
        $this->assertStringContainsString('thread_cache_size', $display);
    }

    /**
     * search command for innodb returns an actual result by checking for known innodb variables.
     */
    public function testSearch()
    {
        $commandTester = $this->getCommand(['--format' => 'csv', 'search'   => 'Innodb%']);

        $databaseHelper = $this->getDatabaseHelper();

        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('innodb_concurrency_tickets', $display);
        // innodb_force_load_corrupted Introduced in 5.6.3
        if (-1 < version_compare($databaseHelper->getMysqlVariable('version'), '5.6.3')) {
            $this->assertStringContainsString('innodb_force_load_corrupted', $display);
        }

        $this->assertStringContainsString('innodb_log_file_size', $display);
        $this->assertMatchesRegularExpression('~innodb_(?:file|read)_io_threads~', $display);
    }

    /**
     * rounding is humanize with K/M/G quantifier *and* --rounding number of digits
     */
    public function testRounding()
    {
        $commandTester = $this->getCommand(['--format'   => 'csv', '--rounding' => '2', 'search'     => '%size%']);

        $databaseHelper = $this->getDatabaseHelper();

        $display = $commandTester->getDisplay();

        $this->assertMatchesRegularExpression('~myisam_max_sort_file_size,[0-9\.]+[A-Z]~', $commandTester->getDisplay());

        // max_binlog_stmt_cache_size Introduced in 5.5.9
        if (-1 < version_compare($databaseHelper->getMysqlVariable('version'), '5.5.9')) {
            $this->assertMatchesRegularExpression('~max_binlog_stmt_cache_size,[0-9\.]+[A-Z]~', $display);
        }
    }
}
