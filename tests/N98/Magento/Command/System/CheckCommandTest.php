<?php

namespace N98\Magento\Command\System;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class CheckCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('sys:check');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Check: Filesystem/', $commandTester->getDisplay());
        $this->assertRegExp('/Check: PHP/', $commandTester->getDisplay());
        $this->assertRegExp('/Check: Security/', $commandTester->getDisplay());
        $this->assertRegExp('/Check: MySQL/', $commandTester->getDisplay());
    }
}