<?php

namespace N98\Magento\Command\System\Setup;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

/**
 * Class RemoveCommandTest
 * @package N98\Magento\Command\System\Setup
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommandTest extends TestCase
{

    public function testRemoveModule()
    {
        $mockAdapter = $this->getMock('\Varien_Db_Adapter_Interface');
        $mockAdapter->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $coreResource = $this->getMock('\Mage_Core_Model_Resource');
        $coreResource->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($mockAdapter));

        $command = $this->getMockBuilder('\N98\Magento\Command\System\Setup\RemoveCommand')
            ->setMethods(array('_getResourceSingleton'))
            ->getMock();

        $command->expects($this->once())
            ->method('_getResourceSingleton')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->will($this->returnValue($coreResource));

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
        ));

        $this->assertContains(
            'Successfully removed setup resource: "weee_setup" from module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testRemoveBySetupName()
    {

        $mockAdapter = $this->getMock('\Varien_Db_Adapter_Interface');
        $mockAdapter->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $coreResource = $this->getMock('\Mage_Core_Model_Resource');
        $coreResource->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($mockAdapter));

        $command = $this->getMockBuilder('\N98\Magento\Command\System\Setup\RemoveCommand')
            ->setMethods(array('_getResourceSingleton'))
            ->getMock();

        $command->expects($this->once())
            ->method('_getResourceSingleton')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->will($this->returnValue($coreResource));

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'weee_setup'
        ));

        $this->assertContains(
            'Successfully removed setup resource: "weee_setup" from module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testRemoveBySetupNameFailure()
    {

        $mockAdapter = $this->getMock('\Varien_Db_Adapter_Interface');
        $mockAdapter->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $coreResource = $this->getMock('\Mage_Core_Model_Resource');
        $coreResource->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($mockAdapter));

        $coreResource->expects($this->once())
            ->method('getTableName')
            ->with('core_resource')
            ->will($this->returnValue('core_resource'));

        $command = $this->getMockBuilder('\N98\Magento\Command\System\Setup\RemoveCommand')
            ->setMethods(array('_getResourceSingleton'))
            ->getMock();

        $command->expects($this->once())
            ->method('_getResourceSingleton')
            ->with('core/resource', 'Mage_Core_Model_Resource')
            ->will($this->returnValue($coreResource));

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'weee_setup'
        ));

        $this->assertContains(
            'Could not remove setup resource: "weee_setup" from module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }

    public function testSetupNameNotFound()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);

        $this->setExpectedException(
            'InvalidArgumentException',
            'Error no setup found with the name: "no_setup_exists"'
        );

        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'no_setup_exists'
        ));
    }

    public function testModuleDoesNotExist()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);

        $this->setExpectedException('InvalidArgumentException', 'No module found with name: "I_DO_NOT_EXIST"');
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'I_DO_NOT_EXIST',
        ));
    }

    public function testCommandReturnsEarlyIfNoSetupResourcesForModule()
    {

        $command = $this->getMockBuilder('\N98\Magento\Command\System\Setup\RemoveCommand')
            ->setMethods(array('getModuleSetupResources'))
            ->getMock();

        $command->expects($this->once())
            ->method('getModuleSetupResources')
            ->with('Mage_Weee')
            ->will($this->returnValue(array()));

        $application = $this->getApplication();
        $application->add($command);
        $command = $this->getApplication()->find('sys:setup:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
            'module'    => 'Mage_Weee',
            'setup'     => 'weee_setup'
        ));

        $this->assertContains(
            'No setup resources found for module: "Mage_Weee"',
            $commandTester->getDisplay()
        );
    }
}
