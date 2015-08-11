<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class CreateUserCommandTest
 */
class CreateUserCommandTest extends TestCase
{
    protected $command;
    protected $userModel;
    protected $roleModel;
    protected $rulesModel;
    protected $commandName = 'admin:user:create';

    public function setUp()
    {
        $this->command = $this->getMockBuilder('\N98\Magento\Command\Admin\User\CreateUserCommand')
            ->setMethods(array('getUserModel', 'getRoleModel', 'getRulesModel'))
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(array('setData', 'save', 'setRoleIds', 'getUserId', 'setRoleUserId', 'saveRelations'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects($this->any())
            ->method('getUserModel')
            ->will($this->returnValue($this->userModel));

        $this->roleModel = $this->getMockBuilder('Mage_Admin_Model_Role')
            ->setMethods(array('load', 'getId', 'setName', 'setRoleType', 'save'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects($this->any())
            ->method('getRoleModel')
            ->will($this->returnValue($this->roleModel));

        $this->rulesModel = $this->getMockBuilder('Mage_Admin_Model_Rules')
            ->setMethods(array('setRoleId', 'setResources', 'saveRel'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->expects($this->any())
            ->method('getRulesModel')
            ->will($this->returnValue($this->rulesModel));
    }

    public function testArgumentPromptsWhenNotPresent()
    {
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask', 'askHiddenResponse'));

        $dialog->expects($this->at(0))
            ->method('ask')
            ->will($this->returnValue('aydin'));

        $dialog->expects($this->at(1))
            ->method('ask')
            ->will($this->returnValue('aydin@hotmail.co.uk'));

        $dialog->expects($this->at(2))
            ->method('askHiddenResponse')
            ->will($this->returnValue('p4ssw0rd'));

        $dialog->expects($this->at(3))
            ->method('ask')
            ->will($this->returnValue('Aydin'));

        $dialog->expects($this->at(4))
            ->method('ask')
            ->will($this->returnValue('Hassan'));

        $this->roleModel
            ->expects($this->once())
            ->method('load')
            ->with('Administrators', 'role_name')
            ->will($this->returnValue($this->roleModel));

        $this->roleModel
            ->method('getId')
            ->will($this->returnValue(9));

        $this->userModel
            ->expects($this->at(0))
            ->method('setData')
            ->with(array(
                'username'  => 'aydin',
                'firstname' => 'Aydin',
                'lastname'  => 'Hassan',
                'email'     => 'aydin@hotmail.co.uk',
                'password'  => 'p4ssw0rd',
                'is_active' => 1
            ))
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->once(2))
            ->method('setRoleIds')
            ->with(array(9))
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getUserId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('setRoleUserId')
            ->with(2)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->once())
            ->method('saveRelations');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'role'      => 'Administrators',
        ));

        $this->assertContains('User aydin successfully created', $commandTester->getDisplay());
    }

    public function testInvalidRole()
    {
        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $this->roleModel
            ->expects($this->once())
            ->method('load')
            ->with('invalid role', 'role_name')
            ->will($this->returnValue($this->roleModel));

        $this->roleModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'username'  => 'aydin',
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'email'     => 'aydin@hotmail.co.uk',
            'password'  => 'p4ssw0rd',
            'role'      => 'invalid role',
        ));

        $this->assertContains('Role was not found', $commandTester->getDisplay());
    }

    public function testCreatingDevelopmentRole()
    {
        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $this->roleModel
            ->expects($this->once())
            ->method('load')
            ->with('Development', 'role_name')
            ->will($this->returnValue($this->roleModel));

        $this->roleModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->roleModel
            ->expects($this->once())
            ->method('setName')
            ->with('Development')
            ->will($this->returnValue($this->roleModel));

        $this->roleModel
            ->expects($this->once())
            ->method('setRoleType')
            ->with('G')
            ->will($this->returnValue($this->roleModel));

        $this->roleModel
            ->expects($this->once())
            ->method('save');

        $this->roleModel
            ->expects($this->at(5))
            ->method('getId')
            ->will($this->returnValue(5));

        $this->rulesModel
            ->expects($this->once())
            ->method('setRoleId')
            ->with(5)
            ->will($this->returnValue($this->rulesModel));

        $this->rulesModel
            ->expects($this->once())
            ->method('setResources')
            ->with(array('all'))
            ->will($this->returnValue($this->rulesModel));

        $this->rulesModel
            ->expects($this->once())
            ->method('saveRel');

        $this->userModel
            ->expects($this->at(0))
            ->method('setData')
            ->with(array(
                'username'  => 'aydin',
                'firstname' => 'Aydin',
                'lastname'  => 'Hassan',
                'email'     => 'aydin@hotmail.co.uk',
                'password'  => 'p4ssw0rd',
                'is_active' => 1
            ))
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue($this->userModel));

        $this->roleModel
            ->expects($this->at(6))
            ->method('getId')
            ->will($this->returnValue(5));

        $this->userModel
            ->expects($this->once(2))
            ->method('setRoleIds')
            ->with(array(5))
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->at(3))
            ->method('getUserId')
            ->will($this->returnValue(2));

        $this->userModel
            ->expects($this->once())
            ->method('setRoleUserId')
            ->with(2)
            ->will($this->returnValue($this->userModel));

        $this->userModel
            ->expects($this->once())
            ->method('saveRelations');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'username'  => 'aydin',
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'email'     => 'aydin@hotmail.co.uk',
            'password'  => 'p4ssw0rd',
        ));

        $this->assertContains('The role Development was automatically created', $commandTester->getDisplay());
        $this->assertContains('User aydin successfully created', $commandTester->getDisplay());
    }
}
