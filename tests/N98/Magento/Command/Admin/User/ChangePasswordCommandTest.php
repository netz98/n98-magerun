<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ChangePasswordCommandTest
 */
final class ChangePasswordCommandTest extends TestCase
{
    private $command;

    private $userModel;

    private $commandName = 'admin:user:change-password';

    protected function setUp(): void
    {
        $this->command = $this->getMockBuilder(ChangePasswordCommand::class)
            ->setMethods(['getUserModel'])
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(['loadByUsername', 'load', 'getId', 'setPassword', 'validate', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getUserModel')
            ->willReturn($this->userModel);
    }

    public function testCanChangePassword()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('aydin')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(2);

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
            ['command'   => $command->getName(), 'username'  => 'aydin', 'password'  => 'password'],
        );

        $this->assertStringContainsString('Password successfully changed', $commandTester->getDisplay());
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

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find($this->commandName);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'username'  => 'notauser']);

        $this->assertStringContainsString('User was not found', $commandTester->getDisplay());
    }
}
