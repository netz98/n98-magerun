<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ChangeStatusCommandTest
 */
class ChangeStatusCommandTest extends TestCase
{
    protected $command;
    protected $userModel;
    protected $commandName = 'admin:user:change-status';

    public function setUp()
    {
        $this->command = $this->getMockBuilder('\N98\Magento\Command\Admin\User\ChangeStatusCommand')
            ->setMethods(array('getUserModel'))
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(array('loadByUsername', 'load', 'getId', 'validate', 'getIsActive', 'setIsActive', 'save', 'getUsername'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects($this->any())
            ->method('getUserModel')
            ->will($this->returnValue($this->userModel));
    }

    public function testCanEnableByUser()
    {
        $username = 'aydin';
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with($username)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects($this->at(4))
            ->method('getIsActive')
            ->will($this->returnValue(0));

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects($this->at(7))
            ->method('getIsActive')
            ->will($this->returnValue(1));

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue($username));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => $username,
            )
        );

        $this->assertContains("User $username is now active", $commandTester->getDisplay());
    }

    public function testCanDisableUser()
    {
        $username = 'aydin';
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with($username)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects($this->at(4))
            ->method('getIsActive')
            ->will($this->returnValue(1));

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(0);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects($this->at(7))
            ->method('getIsActive')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue($username));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => $username,
            )
        );

        $this->assertContains("User $username is now inactive", $commandTester->getDisplay());
    }

    public function testCanToggleUserByEmail()
    {
        $username = 'aydin';
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with($username)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(0));

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects($this->at(5))
            ->method('getIsActive')
            ->will($this->returnValue(0));

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects($this->at(8))
            ->method('getIsActive')
            ->will($this->returnValue(1));

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue($username));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => $username,
            )
        );

        $this->assertContains("User $username is now active", $commandTester->getDisplay());
    }

    public function testReturnEarlyIfUserNotFound()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('notauser')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->with('notauser', 'email')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(null));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        $this->assertContains('User was not found', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $userEmail = 'aydin@hotmail.co.uk';
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->once())
            ->method('ask')
            ->will($this->returnValue($userEmail));

        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with($userEmail)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->method('getUsername')
            ->will($this->returnValue('aydin'));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
        ));

        $this->assertContains("User aydin is now inactive", $commandTester->getDisplay());
    }
}
