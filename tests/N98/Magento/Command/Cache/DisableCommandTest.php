<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DisableCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DisableCommand());

        $command = $this->getApplication()->find('cache:disable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertMatchesRegularExpression('/Caches disabled/', $commandTester->getDisplay());
    }

    public function testExecuteMultipleCaches()
    {
        $application = $this->getApplication();
        $application->add(new DisableCommand());

        $command = $this->getApplication()->find('cache:disable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'code'    => 'eav,config']
        );

        self::assertMatchesRegularExpression('/Cache config disabled/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/Cache eav disabled/', $commandTester->getDisplay());
    }
}
