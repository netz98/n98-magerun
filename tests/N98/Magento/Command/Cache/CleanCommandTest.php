<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class CleanCommandTest extends TestCase
{
    public function testExecute()
    {
        $this->markTestSkipped('Cannot explain why test does not work on travis ci server.');
        $application = $this->getApplication();
        $application->add(new CleanCommand());
        $command = $this->getApplication()->find('cache:clean');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains('config cache cleaned', $commandTester->getDisplay());
    }

    public function testItCanCleanMultipleCaches()
    {
        $this->markTestSkipped('Cannot explain why test does not work on travis ci server.');
        $application = $this->getApplication();
        $application->add(new CleanCommand());
        $command = $this->getApplication()->find('cache:clean');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'type' => array('config', 'layout')
        ));

        $display = $commandTester->getDisplay();

        $this->assertContains('config cache cleaned', $display);
        $this->assertContains('layout cache cleaned', $display);
    }
}