<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('sys:check');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/SETTINGS/', $commandTester->getDisplay());
        $this->assertRegExp('/FILESYSTEM/', $commandTester->getDisplay());
        $this->assertRegExp('/PHP/', $commandTester->getDisplay());
        $this->assertRegExp('/SECURITY/', $commandTester->getDisplay());
        $this->assertRegExp('/MYSQL/', $commandTester->getDisplay());
    }
}
