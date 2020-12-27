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
        $display = $commandTester->getDisplay();

        self::assertContains('Threads_connected', $display);
        self::assertContains('Innodb_buffer_pool_wait_free', $display);
        self::assertContains('InnoDB Buffer Pool hit', $display);
        self::assertContains('Full table scans', $display);
    }

    public function testSearch()
    {
        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
            'search'   => 'Innodb%',
        ));

        $display = $commandTester->getDisplay();

        self::assertContains('Innodb_buffer_pool_read_ahead_rnd', $display);
        self::assertContains('Innodb_buffer_pool_wait_free', $display);
        self::assertContains('InnoDB Buffer Pool hit', $display);
        self::assertContains('Innodb_dblwr_pages_written', $display);
        self::assertContains('Innodb_os_log_written', $display);
    }

    public function testRounding()
    {
        $commandTester = $this->getCommand(array(
            '--format'   => 'csv',
            '--rounding' => '2',
            'search'     => '%size%',
        ));
        self::assertRegExp(
            '~Innodb_page_size,[0-9\.]+K,~',
            $commandTester->getDisplay()
        );
    }
}
