<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class DeleteUserCommandTest
 */
class DeleteUserCommandTest extends TestCase
{
    protected $command;
    protected $userModel;

    public function setUp()
    {
        $this->command = $this->getMockBuilder('\N98\Magento\Command\Admin\User\DeleteUserCommand')
            ->setMethods(array('getUserModel'))
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(array('loadByUsername', 'load', 'getId', 'delete'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects(self::any())
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
            array(
                'command'   => $command->getName(),
                'id'        => 'aydin',
                '--force'   => true,
            )
        );

        self::assertContains('User was successfully deleted', $commandTester->getDisplay());
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
            array(
                'command'   => $command->getName(),
                'id'        => 'aydin@hotmail.co.uk',
                '--force'   => true,
            )
        );

        self::assertContains('User was successfully deleted', $commandTester->getDisplay());
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
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        self::assertContains('User was not found', $commandTester->getDisplay());
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

        $exception = new \Exception("Error!");
        $this->userModel
            ->expects(self::once())
            ->method('delete')
            ->will(self::throwException($exception));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => 'aydin@hotmail.co.uk',
                '--force'   => true,
            )
        );

        self::assertContains('Error!', $commandTester->getDisplay());
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

        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->setMethods(['askConfirmation'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(true);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        self::assertContains('User was successfully deleted', $commandTester->getDisplay());
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

        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->setMethods(['askConfirmation'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ]);

        self::assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects(self::once())
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
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            '--force'   => true,
        ));

        self::assertContains('User was successfully deleted', $commandTester->getDisplay());
    }
}
