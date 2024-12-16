<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('admin:user:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString('id', $commandTester->getDisplay());
        $this->assertStringContainsString('user', $commandTester->getDisplay());
        $this->assertStringContainsString('email', $commandTester->getDisplay());
        $this->assertStringContainsString('status', $commandTester->getDisplay());
    }
}
