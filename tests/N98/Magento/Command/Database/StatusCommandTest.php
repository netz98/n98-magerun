<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class StatusCommandTest extends TestCase
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
        $command = $this->getApplication()->find('db:status');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName()], $options)
        );
        return $commandTester;
    }

    public function testExecute()
    {
        $commandTester = $this->getCommand(['--format' => 'csv']);
        $display = $commandTester->getDisplay();

        self::assertStringContainsString('Threads_connected', $display);
        self::assertStringContainsString('Innodb_buffer_pool_wait_free', $display);
        self::assertStringContainsString('InnoDB Buffer Pool hit', $display);
        self::assertStringContainsString('Full table scans', $display);
    }

    public function testSearch()
    {
        $commandTester = $this->getCommand(['--format' => 'csv', 'search'   => 'Innodb%']);

        $display = $commandTester->getDisplay();

        self::assertStringContainsString('Innodb_buffer_pool_read_ahead_rnd', $display);
        self::assertStringContainsString('Innodb_buffer_pool_wait_free', $display);
        self::assertStringContainsString('InnoDB Buffer Pool hit', $display);
        self::assertStringContainsString('Innodb_dblwr_pages_written', $display);
        self::assertStringContainsString('Innodb_os_log_written', $display);
    }

    public function testRounding()
    {
        $commandTester = $this->getCommand(['--format'   => 'csv', '--rounding' => '2', 'search'     => '%size%']);
        self::assertMatchesRegularExpression(
            '~Innodb_page_size,[0-9\.]+K,~',
            $commandTester->getDisplay()
        );
    }
}
