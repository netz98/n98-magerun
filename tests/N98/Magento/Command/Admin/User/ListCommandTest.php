<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('admin:user:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertStringContainsString('id', $commandTester->getDisplay());
        self::assertStringContainsString('user', $commandTester->getDisplay());
        self::assertStringContainsString('email', $commandTester->getDisplay());
        self::assertStringContainsString('status', $commandTester->getDisplay());
    }
}
