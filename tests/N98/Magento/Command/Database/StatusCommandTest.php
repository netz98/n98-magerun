<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class StatusCommandTest extends TestCase
{
    /**
     * @return CommandTester
     */
    private function getCommand(array $options)
    {
        $application = $this->getApplication();
        $application->add(new StatusCommand());

        $command = $this->getApplication()->find('db:status');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName()], $options),
        );
        return $commandTester;
    }

    public function testExecute()
    {
        $commandTester = $this->getCommand(['--format' => 'csv']);
        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('Threads_connected', $display);
        $this->assertStringContainsString('Innodb_buffer_pool_wait_free', $display);
        $this->assertStringContainsString('InnoDB Buffer Pool hit', $display);
        $this->assertStringContainsString('Full table scans', $display);
    }

    public function testSearch()
    {
        $commandTester = $this->getCommand(['--format' => 'csv', 'search'   => 'Innodb%']);

        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('Innodb_buffer_pool_read_ahead_rnd', $display);
        $this->assertStringContainsString('Innodb_buffer_pool_wait_free', $display);
        $this->assertStringContainsString('InnoDB Buffer Pool hit', $display);
        $this->assertStringContainsString('Innodb_dblwr_pages_written', $display);
        $this->assertStringContainsString('Innodb_os_log_written', $display);
    }

    public function testRounding()
    {
        $commandTester = $this->getCommand(['--format'   => 'csv', '--rounding' => '2', 'search'     => '%size%']);
        $this->assertMatchesRegularExpression('~Innodb_page_size,[0-9\.]+K,~', $commandTester->getDisplay());
    }
}
