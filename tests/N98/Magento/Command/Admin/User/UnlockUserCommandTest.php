<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UnlockUserCommandTest
 */
class UnlockUserCommandTest extends TestCase
{



    public function testUnlockAllUsersPromptNo()
    {
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));

        $dialog->expects($this->once())
            ->method('ask')
            ->will($this->returnValue("n"));

        $application = $this->getApplication();
        $command = new UnlockCommand();
        if (!$command->isEnabled()){
            $this->markTestSkipped('UnlockCommand is not enabled.');

        }
        $application->add($command);
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertNotContains('All admins unlocked', $commandTester->getDisplay());
    }

    public function testUnlockAllUsersPromptYes()
    {
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));

        $dialog->expects($this->once())
            ->method('ask')
            ->will($this->returnValue("y"));

        $application = $this->getApplication();
        $command = new UnlockCommand();
        if (!$command->isEnabled()){
            $this->markTestSkipped('UnlockCommand is not enabled.');

        }
        $application->add($command);
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains('All admins unlocked', $commandTester->getDisplay());
    }
}
