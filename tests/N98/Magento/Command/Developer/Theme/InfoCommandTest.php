<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:theme:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()]
        );

        self::assertStringContainsString('base/default', $commandTester->getDisplay());
        self::assertStringContainsString('Design Package Name', $commandTester->getDisplay());
    }
}
