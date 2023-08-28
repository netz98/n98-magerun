<?php

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RemoveCommandTest
 * @package N98\Magento\Command\System\Setup
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommandTest extends TestCase
{
    public function testRemoveModule()
    {
        $mockAdapter = $this->getMockBuilder('\Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();

        $mockAdapter->expects(self::once())
            ->method('delete')
            ->willReturn(1);

        $coreResource = $this->createMock(\Mage_Core_Model_Resource::clas);
        $coreResource->expects(self::once())
            ->method('getConnection')
            ->willReturn($mockAdapter);

        $command = $this->getMockBuilder(RemoveCommand::class)
            ->setMethods(['_getModel'])
            ->getMock();

        $command->expects(self::once())
            ->method('_getModel')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->willReturn($coreResource);

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee']);

        self::assertStringContainsString(
            'Successfully removed setup resource: "weee_setup" from module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testRemoveBySetupName()
    {
        $mockAdapter = $this->getMockBuilder('\Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();

        $mockAdapter->expects(self::once())
            ->method('delete')
            ->willReturn(1);

        $coreResource = $this->createMock('\Mage_Core_Model_Resource');
        $coreResource->expects(self::once())
            ->method('getConnection')
            ->willReturn($mockAdapter);

        $command = $this->getMockBuilder(RemoveCommand::class)
            ->setMethods(['_getModel'])
            ->getMock();

        $command->expects(self::once())
            ->method('_getModel')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->willReturn($coreResource);

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'weee_setup',
        ]);

        self::assertStringContainsString(
            'Successfully removed setup resource: "weee_setup" from module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testRemoveBySetupNameFailure()
    {
        $mockAdapter = $this->getMockBuilder('\Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();

        $mockAdapter->expects(self::once())
            ->method('delete')
            ->willReturn(0);

        $coreResource = $this->createMock('\Mage_Core_Model_Resource');
        $coreResource->expects(self::once())
            ->method('getConnection')
            ->willReturn($mockAdapter);

        $coreResource->expects(self::once())
            ->method('getTableName')
            ->with('core_resource')
            ->willReturn('core_resource');

        $command = $this->getMockBuilder(RemoveCommand::class)
            ->setMethods(['_getModel'])
            ->getMock();

        $command->expects(self::once())
            ->method('_getModel')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->willReturn($coreResource);

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'setup'     => 'weee_setup']);

        self::assertStringContainsString(
            'No entry was found for setup resource: "weee_setup" in module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testSetupNameNotFound()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);

        $this->expectException(
            InvalidArgumentException::class
        );

        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'setup'     => 'no_setup_exists']);
    }

    public function testModuleDoesNotExist()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'I_DO_NOT_EXIST']);
    }

    public function testCommandReturnsEarlyIfNoSetupResourcesForModule()
    {
        $command = $this->getMockBuilder(RemoveCommand::class)
            ->setMethods(['getModuleSetupResources'])
            ->getMock();

        $command->expects(self::once())
            ->method('getModuleSetupResources')
            ->with('Mage_Weee')
            ->willReturn([]);

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'weee_setup',
        ]);

        self::assertStringContainsString(
            'No setup resources found for module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }
}
