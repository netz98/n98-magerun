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

        $this->assertContains('id', $commandTester->getDisplay());
        $this->assertContains('user', $commandTester->getDisplay());
        $this->assertContains('email', $commandTester->getDisplay());
        $this->assertContains('status', $commandTester->getDisplay());
    }
}
