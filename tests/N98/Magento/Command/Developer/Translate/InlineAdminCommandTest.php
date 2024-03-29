<?php

namespace N98\Magento\Command\Developer\Translate;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InlineAdminCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InlineAdminCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:translate:admin');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--on'     => true]
        );
        self::assertStringContainsString('Inline Translation (Admin) enabled', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--off'    => true]
        );

        self::assertStringContainsString('Inline Translation (Admin) disabled', $commandTester->getDisplay());
    }
}
