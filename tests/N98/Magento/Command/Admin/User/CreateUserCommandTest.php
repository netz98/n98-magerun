<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Admin_Model_Roles;
use Mage_Admin_Model_Rules;
use Mage_Admin_Model_User;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class CreateUserCommandTest
 */
final class CreateUserCommandTest extends TestCase
{
    private $command;

    private $userModel;

    private $roleModel;

    private $rulesModel;

    private $commandName = 'admin:user:create';

    protected function setUp(): void
    {
        $this->command = $this->getMockBuilder(CreateUserCommand::class)
            ->setMethods(['getUserModel', 'getRoleModel', 'getRulesModel'])
            ->getMock();

        $this->userModel = $this->getMockBuilder(Mage_Admin_Model_User::class)
            ->setMethods(['setData', 'save', 'setRoleIds', 'getUserId', 'setRoleUserId', 'saveRelations'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getUserModel')
            ->willReturn($this->userModel);

        $this->roleModel = $this->getMockBuilder(Mage_Admin_Model_Roles::class)
            ->setMethods(['load', 'getId', 'setName', 'setRoleType', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getRoleModel')
            ->willReturn($this->roleModel);

        $this->rulesModel = $this->getMockBuilder(Mage_Admin_Model_Rules::class)
            ->setMethods(['setRoleId', 'setResources', 'saveRel'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->command
            ->method('getRulesModel')
            ->willReturn($this->rulesModel);
    }

    public function testArgumentPromptsWhenNotPresent()
    {
        $questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelper->expects(self::at(0))
            ->method('ask')
            ->willReturn('aydin');

        $questionHelper->expects(self::at(1))
            ->method('ask')
            ->willReturn('aydin@hotmail.co.uk');

        $questionHelper->expects(self::at(2))
            ->method('ask')
            ->willReturn('p4ssw0rd');

        $questionHelper->expects(self::at(3))
            ->method('ask')
            ->willReturn('Aydin');

        $questionHelper->expects(self::at(4))
            ->method('ask')
            ->willReturn('Hassan');

        $this->roleModel
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('save')
            ->willReturn($this->userModel);

        $this->userModel
            ->expects($this->once())
            ->method('setRoleIds')
            ->with([9])
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getUserId')
            ->willReturn(2);

        $this->userModel
            ->expects($this->once())
            ->method('setRoleUserId')
            ->with(2)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects($this->once())
            ->method('saveRelations');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find($this->commandName);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'role' => 'Administrators']);

        $this->assertStringContainsString('User aydin successfully created', $commandTester->getDisplay());
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
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'username'  => 'aydin', 'firstname' => 'Aydin', 'lastname'  => 'Hassan', 'email'     => 'aydin@hotmail.co.uk', 'password'  => 'p4ssw0rd', 'role'      => 'invalid role']);

        $this->assertStringContainsString('Role was not found', $commandTester->getDisplay());
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
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->roleModel
            ->expects($this->once())
            ->method('setName')
            ->with('Development')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects($this->once())
            ->method('setRoleType')
            ->with('G')
            ->willReturn($this->roleModel);

        $this->roleModel
            ->expects($this->once())
            ->method('save');

        $this->roleModel
            ->expects(self::at(5))
            ->method('getId')
            ->willReturn(5);

        $this->rulesModel
            ->expects($this->once())
            ->method('setRoleId')
            ->with(5)
            ->willReturn($this->rulesModel);

        $this->rulesModel
            ->expects($this->once())
            ->method('setResources')
            ->with(['all'])
            ->willReturn($this->rulesModel);

        $this->rulesModel
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('save')
            ->willReturn($this->userModel);

        $this->roleModel
            ->expects(self::at(6))
            ->method('getId')
            ->willReturn(5);

        $this->userModel
            ->expects($this->once())
            ->method('setRoleIds')
            ->with([5])
            ->willReturn($this->userModel);

        $this->userModel
            ->expects(self::at(3))
            ->method('getUserId')
            ->willReturn(2);

        $this->userModel
            ->expects($this->once())
            ->method('setRoleUserId')
            ->with(2)
            ->willReturn($this->userModel);

        $this->userModel
            ->expects($this->once())
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

        $this->assertStringContainsString('The role Development was automatically created', $commandTester->getDisplay());
        $this->assertStringContainsString('User aydin successfully created', $commandTester->getDisplay());
    }
}
