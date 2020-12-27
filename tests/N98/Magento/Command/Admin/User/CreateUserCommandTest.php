<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;
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
            ->setMethods(['getUserModel', 'getRoleModel', 'getRulesModel'])
            ->getMock();

        $this->userModel = $this->getMockBuilder('Mage_Admin_Model_User')
            ->setMethods(['setData', 'save', 'setRoleIds', 'getUserId', 'setRoleUserId', 'saveRelations'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getUserModel')
            ->willReturn($this->userModel);

        $this->roleModel = $this->getMockBuilder('Mage_Admin_Model_Role')
            ->setMethods(['load', 'getId', 'setName', 'setRoleType', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getRoleModel')
            ->willReturn($this->roleModel);

        $this->rulesModel = $this->getMockBuilder('Mage_Admin_Model_Rules')
            ->setMethods(array('setRoleId', 'setResources', 'saveRel'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getRulesModel')
            ->willReturn($this->rulesModel);
    }

    public function testArgumentPromptsWhenNotPresent()
    {
        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask', 'askHiddenResponse'])
            ->getMock();

        $dialog->expects(self::at(0))
            ->method('ask')
            ->willReturn('aydin');

        $dialog->expects(self::at(1))
            ->method('ask')
            ->willReturn('aydin@hotmail.co.uk');

        $dialog->expects(self::at(2))
            ->method('askHiddenResponse')
            ->willReturn('p4ssw0rd');

        $dialog->expects(self::at(3))
            ->method('ask')
            ->willReturn('Aydin');

        $dialog->expects(self::at(4))
            ->method('ask')
            ->willReturn('Hassan');

        $this->roleModel
            ->expects(self::once())
            ->method('load')
            ->with('Administrators', 'role_name')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->method('getId')
            ->willReturn(9);

        $this->userModel
            ->expects(self::at(0))
            ->method('setData')
            ->with([
                'username'  => 'aydin',
                'firstname' => 'Aydin',
                'lastname'  => 'Hassan',
                'email'     => 'aydin@hotmail.co.uk',
                'password'  => 'p4ssw0rd',
                'is_active' => 1,
            ])
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::once())
            ->method('save')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::once(2))
            ->method('setRoleIds')
            ->with(array(9))
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getUserId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('setRoleUserId')
            ->with(2)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::once())
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

        self::assertContains('User aydin successfully created', $commandTester->getDisplay());
    }

    public function testInvalidRole()
    {
        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $this->roleModel
            ->expects(self::once())
            ->method('load')
            ->with('invalid role', 'role_name')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects(self::once())
            ->method('getId')
            ->willReturn(null);

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

        self::assertContains('Role was not found', $commandTester->getDisplay());
    }

    public function testCreatingDevelopmentRole()
    {
        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find($this->commandName);

        $this->roleModel
            ->expects(self::once())
            ->method('load')
            ->with('Development', 'role_name')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->roleModel
            ->expects(self::once())
            ->method('setName')
            ->with('Development')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects(self::once())
            ->method('setRoleType')
            ->with('G')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects(self::once())
            ->method('save');

        $this->roleModel
            ->expects(self::at(5))
            ->method('getId')
            ->willReturn(5);

        $this->rulesModel
            ->expects(self::once())
            ->method('setRoleId')
            ->with(5)
            ->willReturn($this->rulesModel);

        $this->rulesModel
            ->expects(self::once())
            ->method('setResources')
            ->with(array('all'))
            ->willReturn($this->rulesModel);

        $this->rulesModel
            ->expects(self::once())
            ->method('saveRel');

        $this->userModel
            ->expects(self::at(0))
            ->method('setData')
            ->with([
                'username'  => 'aydin',
                'firstname' => 'Aydin',
                'lastname'  => 'Hassan',
                'email'     => 'aydin@hotmail.co.uk',
                'password'  => 'p4ssw0rd',
                'is_active' => 1,
            ])
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::once())
            ->method('save')
            ->willReturn($this->userModel);

        $this->roleModel
            ->expects(self::at(6))
            ->method('getId')
            ->willReturn(5);

        $this->userModel
            ->expects(self::once(2))
            ->method('setRoleIds')
            ->with(array(5))
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getUserId')
            ->willReturn(2);

        $this->userModel
            ->expects(self::once())
            ->method('setRoleUserId')
            ->with(2)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::once())
            ->method('saveRelations');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            'username'  => 'aydin',
            'firstname' => 'Aydin',
            'lastname'  => 'Hassan',
            'email'     => 'aydin@hotmail.co.uk',
            'password'  => 'p4ssw0rd',
        ]);

        self::assertContains('The role Development was automatically created', $commandTester->getDisplay());
        self::assertContains('User aydin successfully created', $commandTester->getDisplay());
    }
}
