<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class EnableCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new EnableCommand());

        $command = $this->getApplication()->find('cache:enable');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesRegularExpression('/Caches enabled/', $commandTester->getDisplay());
    }

    public function testExecuteMultipleCaches()
    {
        $application = $this->getApplication();
        $application->add(new DisableCommand());

        $command = $this->getApplication()->find('cache:enable');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'code'    => 'eav,config'],
        );

        $this->assertMatchesRegularExpression('/Cache config enabled/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Cache eav enabled/', $commandTester->getDisplay());
    }
}
