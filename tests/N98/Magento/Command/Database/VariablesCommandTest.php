<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class VariablesCommandTest extends TestCase
{
    /**
     * @param array $options
     *
     * @return CommandTester
     */
    protected function getCommand(array $options)
    {
        $application = $this->getApplication();
        $application->add(new StatusCommand());
        $command = $this->getApplication()->find('db:variables');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(array(
                'command' => $command->getName(),
            ), $options)
        );
        return $commandTester;
    }

    public function testExecute()
    {

        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
        ));
        $display       = $commandTester->getDisplay();

        $this->assertContains('have_query_cache', $display);
        $this->assertContains('innodb_log_buffer_size', $display);
        $this->assertContains('max_connections', $display);
        $this->assertContains('thread_cache_size', $display);
    }

    public function testSearch()
    {
        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
            'search'   => 'Innodb%',
        ));

        $display = $commandTester->getDisplay();

        $this->assertContains('innodb_concurrency_tickets', $display);
        $this->assertContains('innodb_file_format_check', $display);
        $this->assertContains('innodb_force_load_corrupted', $display);
        $this->assertContains('innodb_log_file_size', $display);
        $this->assertContains('innodb_read_io_threads', $display);
    }

    public function testRounding()
    {
        $commandTester = $this->getCommand(array(
            '--format'   => 'csv',
            '--rounding' => '2',
            'search'     => '%size%',
        ));

        $this->assertRegExp('~max_binlog_stmt_cache_size," [0-9\.]+[A-Z]"~', $commandTester->getDisplay());
        $this->assertRegExp('~myisam_max_sort_file_size,"  [0-9\.]+[A-Z]"~', $commandTester->getDisplay());
    }
}
