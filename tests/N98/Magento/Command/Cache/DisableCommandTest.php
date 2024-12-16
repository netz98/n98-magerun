<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DisableCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DisableCommand());

        $command = $this->getApplication()->find('cache:disable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesRegularExpression('/Caches disabled/', $commandTester->getDisplay());
    }

    public function testExecuteMultipleCaches()
    {
        $application = $this->getApplication();
        $application->add(new DisableCommand());

        $command = $this->getApplication()->find('cache:disable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'code'    => 'eav,config'],
        );

        $this->assertMatchesRegularExpression('/Cache config disabled/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Cache eav disabled/', $commandTester->getDisplay());
    }
}
