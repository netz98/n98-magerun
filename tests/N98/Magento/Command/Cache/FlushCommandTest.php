<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FlushCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new FlushCommand());
        $command = $this->getApplication()->find('cache:flush');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertMatchesRegularExpression('/Cache cleared/', $commandTester->getDisplay());
    }
}
