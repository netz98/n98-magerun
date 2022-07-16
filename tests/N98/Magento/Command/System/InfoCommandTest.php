<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('sys:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertRegExp('/Magento System Information/', $commandTester->getDisplay());
        self::assertRegExp('/Install Date/', $commandTester->getDisplay());
        self::assertRegExp('/Crypt Key/', $commandTester->getDisplay());

        // Settings argument
        $commandTester->execute(
            ['command' => $command->getName(), 'key'     => 'version']
        );

        $commandResult = $commandTester->getDisplay();
        self::assertRegExp('/\d+\.\d+\.\d+/', $commandResult);
    }
}
