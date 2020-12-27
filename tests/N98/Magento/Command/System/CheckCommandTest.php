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

        self::assertRegExp('/SETTINGS/', $commandTester->getDisplay());
        self::assertRegExp('/FILESYSTEM/', $commandTester->getDisplay());
        self::assertRegExp('/PHP/', $commandTester->getDisplay());
        self::assertRegExp('/SECURITY/', $commandTester->getDisplay());
        self::assertRegExp('/MYSQL/', $commandTester->getDisplay());
    }
}
