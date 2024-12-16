<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ChangeStatusCommandTest
 */
final class ChangeStatusCommandTest extends TestCase
{
    private $command;

    private $userModel;

    private $commandName = 'admin:user:change-status';

    protected function setUp(): void
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
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(4))
            ->method('getIsActive')
            ->willReturn(0);

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects(self::at(7))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username],
        );

        $this->assertStringContainsString(sprintf('User %s is now active', $username), $commandTester->getDisplay());
    }

    public function testCanDisableUser()
    {
        $username = 'aydin';
        $this->userModel
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(4))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(0);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects(self::at(7))
            ->method('getIsActive')
            ->willReturn(2);

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username],
        );

        $this->assertStringContainsString(sprintf('User %s is now inactive', $username), $commandTester->getDisplay());
    }

    public function testCanToggleUserByEmail()
    {
        $username = 'aydin';
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with($username)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(0);

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects($this->once())
            ->method('validate');

        $this->userModel
            ->expects(self::at(5))
            ->method('getIsActive')
            ->willReturn(0);

        $this->userModel
            ->expects($this->once())
            ->method('setIsActive')
            ->with(1);

        $this->userModel
            ->expects($this->once())
            ->method('save');

        $this->userModel
            ->expects(self::at(8))
            ->method('getIsActive')
            ->willReturn(1);

        $this->userModel
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn($username);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => $username],
        );

        $this->assertStringContainsString(sprintf('User %s is now active', $username), $commandTester->getDisplay());
    }

    public function testReturnEarlyIfUserNotFound()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('notauser')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->userModel
            ->expects($this->once())
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

        $this->assertStringContainsString('User was not found', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $userEmail = 'aydin@hotmail.co.uk';
        $dialog = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects($this->once())
            ->method('ask')
            ->willReturn($userEmail);

        $this->userModel
            ->expects($this->once())
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

        $this->assertStringContainsString('User aydin is now inactive', $commandTester->getDisplay());
    }
}
