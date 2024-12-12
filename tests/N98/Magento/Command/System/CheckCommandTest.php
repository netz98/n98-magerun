<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());

        $command = $this->getApplication()->find('sys:check');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesRegularExpression('/SETTINGS/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/FILESYSTEM/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/PHP/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/SECURITY/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/MYSQL/', $commandTester->getDisplay());
    }
}
