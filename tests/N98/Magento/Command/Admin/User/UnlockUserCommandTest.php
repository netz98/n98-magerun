<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UnlockUserCommandTest
 */
class UnlockUserCommandTest extends TestCase
{
    private function getCommand()
    {
        $command = new UnlockCommand();
        $command->setApplication($this->getApplication());
        if (!$command->isEnabled()) {
            $this->markTestSkipped('UnlockCommand is not enabled.');
        }
        return $command;
    }

    public function testUnlockAllUsersPromptNo()
    {
        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects($this->once())
            ->method('ask')
            ->willReturn('n');

        $application = $this->getApplication();
        $application->add($this->getCommand());
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertNotContains('All admins unlocked', $commandTester->getDisplay());
    }

    public function testUnlockAllUsersPromptYes()
    {
        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects($this->once())
            ->method('ask')
            ->willReturn('y');

        $application = $this->getApplication();
        $application->add($this->getCommand());
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains('All admins unlocked', $commandTester->getDisplay());
    }
}
