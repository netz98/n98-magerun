<?php

namespace N98\Magento\Command\Admin\User;

use Exception;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class DeleteUserCommandTest
 */
class DeleteUserCommandTest extends TestCase
{
    protected $command;
    protected $userModel;

    public function setUp(): void
    {
        $this->command = $this->getMockBuilder(DeleteUserCommand::class)
            ->setMethods(['getUserModel'])
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(['loadByUsername', 'load', 'getId', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getUserModel')
            ->willReturn($this->userModel);
    }

    public function testCanDeleteByUserName()
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
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::never())
            ->method('load');

        $this->userModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'aydin', '--force'   => true]
        );

        self::assertStringContainsString('User was successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->userModel
            ->expects(self::once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'aydin@hotmail.co.uk', '--force'   => true]
        );

        self::assertStringContainsString('User was successfully deleted', $commandTester->getDisplay());
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
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'id'        => 'notauser']);

        self::assertStringContainsString('User was not found', $commandTester->getDisplay());
    }

    public function testMessageIsPrintedIfErrorDeleting()
    {
        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->userModel
            ->expects(self::once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $exception = new Exception('Error!');
        $this->userModel
            ->expects(self::once())
            ->method('delete')
            ->will(self::throwException($exception));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'aydin@hotmail.co.uk', '--force'   => true]
        );

        self::assertStringContainsString('Error!', $commandTester->getDisplay());
    }

    public function testConfirmationTrueReplyDeletesUser()
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
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelper->expects(self::once())
            ->method('ask')
            ->willReturn(true);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'id'        => 'notauser']);

        self::assertStringContainsString('User was successfully deleted', $commandTester->getDisplay());
    }

    public function testConfirmationFalseReplyDoesNotDeleteUser()
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
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::never())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelper->expects(self::once())
            ->method('ask')
            ->willReturn(false);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ]);

        self::assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelper->expects(self::once())
            ->method('ask')
            ->willReturn('aydin@hotmail.co.uk');

        $this->userModel
            ->expects(self::once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->userModel
            ->expects(self::once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        // We override the standard helper with our mock
        $command->getHelperSet()->set($questionHelper, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), '--force'   => true]);

        self::assertStringContainsString('User was successfully deleted', $commandTester->getDisplay());
    }
}
