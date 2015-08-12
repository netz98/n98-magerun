<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

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
        $display       = $commandTester->getDisplay();

        $this->assertContains('Threads_connected', $display);
        $this->assertContains('Innodb_buffer_pool_wait_free', $display);
        $this->assertContains('InnoDB Buffer Pool hit', $display);
        $this->assertContains('Full table scans', $display);
    }

    public function testSearch()
    {
        $commandTester = $this->getCommand(array(
            '--format' => 'csv',
            'search'   => 'Innodb%',
        ));

        $display = $commandTester->getDisplay();

        $this->assertContains('Innodb_buffer_pool_read_ahead_rnd', $display);
        $this->assertContains('Innodb_buffer_pool_wait_free', $display);
        $this->assertContains('InnoDB Buffer Pool hit', $display);
        $this->assertContains('Innodb_dblwr_pages_written', $display);
        $this->assertContains('Innodb_os_log_written', $display);
    }

    public function testRounding()
    {
        $commandTester = $this->getCommand(array(
            '--format'   => 'csv',
            '--rounding' => '2',
            'search'     => '%size%',
        ));
        $this->assertRegExp(
            '~Innodb_page_size,[0-9\.]+K,~',
            $commandTester->getDisplay()
        );
    }
}
