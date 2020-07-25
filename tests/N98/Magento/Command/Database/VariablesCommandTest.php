<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Tester\CommandTester;

class VariablesCommandTest extends TestCase
{
    /**
     * @var StatusCommand
     */
    private $statusCommand;

    /**
     * @param array $options
     *
     * @return CommandTester
     */
    protected function getCommand(array $options)
    {
        $this->statusCommand = new StatusCommand();

        $application = $this->getApplication();
        $application->add($this->statusCommand);
        $command = $this->getApplication()->find('db:variables');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(array(
                'command' => $command->getName(),
            ), $options)
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
        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
        ));
        $display = $commandTester->getDisplay();

        $this->assertContains('have_query_cache', $display);
        $this->assertContains('innodb_log_buffer_size', $display);
        $this->assertContains('max_connections', $display);
        $this->assertContains('thread_cache_size', $display);
    }

    /**
     * @test search command for innodb returns an actual result by checking for known innodb variables.
     */
    public function testSearch()
    {
        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
            'search'   => 'Innodb%',
        ));

        $dbHelper = $this->getDatabaseHelper();

        $display = $commandTester->getDisplay();

        $this->assertContains('innodb_concurrency_tickets', $display);
        // innodb_force_load_corrupted Introduced in 5.6.3
        if (-1 < version_compare($dbHelper->getMysqlVariable('version'), '5.6.3')) {
            $this->assertContains('innodb_force_load_corrupted', $display);
        }
        $this->assertContains('innodb_log_file_size', $display);
        $this->assertRegExp('~innodb_(?:file|read)_io_threads~', $display);
    }

    /**
     * @test rounding which is humanize with K/M/G quantifier *and* --rounding number of digits
     */
    public function testRounding()
    {
        $commandTester = $this->getCommand(array(
            '--format'   => 'csv',
            '--rounding' => '2',
            'search'     => '%size%',
        ));

        $dbHelper = $this->getDatabaseHelper();

        $display = $commandTester->getDisplay();

        $this->assertRegExp('~myisam_max_sort_file_size,[0-9\.]+[A-Z]~', $commandTester->getDisplay());

        // max_binlog_stmt_cache_size Introduced in 5.5.9
        if (-1 < version_compare($dbHelper->getMysqlVariable('version'), '5.5.9')) {
            $this->assertRegExp('~max_binlog_stmt_cache_size,[0-9\.]+[A-Z]~', $display);
        }
    }
}
