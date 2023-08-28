<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertMatchesRegularExpression('/config/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/collections/', $commandTester->getDisplay());
    }
}
