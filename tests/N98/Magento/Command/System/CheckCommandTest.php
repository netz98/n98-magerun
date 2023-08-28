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
        $commandTester->execute(['command' => $command->getName()]);

        self::assertMatchesRegularExpression('/SETTINGS/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/FILESYSTEM/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/PHP/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/SECURITY/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/MYSQL/', $commandTester->getDisplay());
    }
}
