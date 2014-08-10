<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\PHPUnit\TestCase;
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
            ->expects($this->any())
            ->method('getUserModel')
            ->will($this->returnValue($this->userModel));
    }

    public function testCanDeleteByUserName()
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
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->never())
            ->method('load');

        $this->userModel
            ->expects($this->once())
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

        $this->assertContains('User was successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
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

        $this->assertContains('User was successfully deleted', $commandTester->getDisplay());
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
        $command = $this->getApplication()->find('admin:user:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        $this->assertContains('User was not found', $commandTester->getDisplay());
    }

    public function testMessageIsPrintedIfErrorDeleting()
    {
        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $exception = new \Exception("Error!");
        $this->userModel
            ->expects($this->once())
            ->method('delete')
            ->will($this->throwException($exception));

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

        $this->assertContains('Error!', $commandTester->getDisplay());
    }

    public function testConfirmationTrueReplyDeletesUser()
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
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('askConfirmation'));
        $dialog->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(true));

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');


        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        $this->assertContains('User was successfully deleted', $commandTester->getDisplay());
    }

    public function testConfirmationFalseReplyDoesNotDeleteUser()
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
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->never())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('admin:user:delete');

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('askConfirmation'));
        $dialog->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(false));

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');


        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'id'        => 'notauser',
        ));

        $this->assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testIfNoIdIsPresentItIsPromptedFor()
    {
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->once())
            ->method('ask')
            ->will($this->returnValue('aydin@hotmail.co.uk'));


        $this->userModel
            ->expects($this->once())
            ->method('loadByUsername')
            ->with('aydin@hotmail.co.uk')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->userModel
            ->expects($this->once())
            ->method('load')
            ->with('aydin@hotmail.co.uk', 'email')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
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

        $this->assertContains('User was successfully deleted', $commandTester->getDisplay());
    }
}
