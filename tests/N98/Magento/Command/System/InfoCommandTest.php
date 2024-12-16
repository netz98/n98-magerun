<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());

        $command = $this->getApplication()->find('sys:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesRegularExpression('/Magento System Information/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Install Date/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Crypt Key/', $commandTester->getDisplay());

        // Settings argument
        $commandTester->execute(
            ['command' => $command->getName(), 'key'     => 'version'],
        );

        $commandResult = $commandTester->getDisplay();
        $this->assertMatchesRegularExpression('/\d+\.\d+\.\d+/', $commandResult);
    }
}
