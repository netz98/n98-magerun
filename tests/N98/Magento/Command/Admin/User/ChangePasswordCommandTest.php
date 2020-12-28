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
            ->method('getUserModel')
            ->willReturn($this->userModel);
    }

    public function testCanChangePassword()
    {
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with('aydin')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('validate');

        $this->userModel
            ->expects(self::once())
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

        self::assertContains('Password successfully changed', $commandTester->getDisplay());
    }

    public function testReturnEarlyIfUserNotFound()
    {
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with('notauser')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'username'  => 'notauser',
        ));

        self::assertContains('User was not found', $commandTester->getDisplay());
    }
}
