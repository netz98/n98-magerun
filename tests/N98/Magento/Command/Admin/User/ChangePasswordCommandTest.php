<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ChangePasswordCommandTest
 */
class ChangePasswordCommandTest extends TestCase
{
    protected $command;
    protected $userModel;
    protected $commandName = 'admin:user:change-password';

    public function setUp()
    {
        $this->command = $this->getMockBuilder('\N98\Magento\Command\Admin\User\ChangePasswordCommand')
            ->setMethods(array('getUserModel'))
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(array('loadByUsername', 'load', 'getId', 'setPassword', 'validate', 'save'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects($this->any())
            ->method('getUserModel')
            ->will($this->returnValue($this->userModel));
    }

    public function testCanChangePassword()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('aydin')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'username'  => 'aydin',
                'password'  => 'password',
            )
        );

        $this->assertContains('Password successfully changed', $commandTester->getDisplay());
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

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'username'  => 'notauser',
        ));

        $this->assertContains('User was not found', $commandTester->getDisplay());
    }
}
