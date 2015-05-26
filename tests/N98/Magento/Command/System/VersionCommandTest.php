<?php

namespace N98\Magento\Command\System;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class VersionCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new VersionCommand());
        $command = $this->getApplication()->find('sys:version');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Magento Version Information/', $commandTester->getDisplay());
        $this->assertRegExp('/Community|Enterprise/', $commandTester->getDisplay());
        $this->assertRegExp('/Edition/', $commandTester->getDisplay());
    }
}