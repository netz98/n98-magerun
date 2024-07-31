<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ChangeStatusCommandTest
 */
class ChangeStatusCommandTest extends TestCase
{
    protected $command;
    protected $userModel;
    protected $commandName = 'admin:user:change-status';

    public function setUp(): void
    {
        $this->command = $this->getMockBuilder(ChangeStatusCommand::class)
            ->setMethods(['getUserModel'])
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(['loadByUsername', 'load', 'getId', 'validate', 'getIsActive', 'setIsActive', 'save', 'getUsername'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getUserModel')
            ->willReturn($this->userModel);
    }

    public function testCanEnableByUser()
    {
        $username = 'aydin';
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with($username)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(4))
            ->method('getIsActive')
            ->willReturn(0);

        $this->userModel
            ->expects(self::once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects(self::once())
            ->method('save');

        $this->userModel
            ->expects(self::at(7))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects(self::once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username]
        );

        self::assertStringContainsString("User $username is now active", $commandTester->getDisplay());
    }

    public function testCanDisableUser()
    {
        $username = 'aydin';
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with($username)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(4))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects(self::once())
            ->method('setIsActive')
            ->with(0);

        $this->userModel
            ->expects(self::once())
            ->method('save');

        $this->userModel
            ->expects(self::at(7))
            ->method('getIsActive')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username]
        );

        self::assertStringContainsString("User $username is now inactive", $commandTester->getDisplay());
    }

    public function testCanToggleUserByEmail()
    {
        $username = 'aydin';
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with($username)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(0);

        $this->userModel
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(5))
            ->method('getIsActive')
            ->willReturn(0);

        $this->userModel
            ->expects(self::once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects(self::once())
            ->method('save');

        $this->userModel
            ->expects(self::at(8))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects(self::once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username]
        );

        self::assertStringContainsString("User $username is now active", $commandTester->getDisplay());
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

        $this->userModel
            ->expects(self::once())
            ->method('load')
            ->with('notauser', 'email')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(null);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'id'        => 'notauser']);

        self::assertStringContainsString('User was not found', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $userEmail = 'aydin@hotmail.co.uk';
        $dialog = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('ask')
            ->willReturn($userEmail);

        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with($userEmail)
            ->willReturn($this->userModel);

        $this->userModel
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->method('getUsername')
            ->willReturn('aydin');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName()]);

        self::assertStringContainsString('User aydin is now inactive', $commandTester->getDisplay());
    }
}
