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
        $commandTester->execute(array('command' => $command->getName()));

        self::assertContains('id', $commandTester->getDisplay());
        self::assertContains('user', $commandTester->getDisplay());
        self::assertContains('email', $commandTester->getDisplay());
        self::assertContains('status', $commandTester->getDisplay());
    }
}
