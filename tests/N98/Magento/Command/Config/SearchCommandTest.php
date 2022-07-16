<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SearchCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('config:search');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'text'      => 'This message will be shown']
        );
        self::assertStringContainsString('Found a field with a match', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'text'      => 'xyz1234567890']
        );
        self::assertStringContainsString('No matches for xyz1234567890', $commandTester->getDisplay());
    }
}
