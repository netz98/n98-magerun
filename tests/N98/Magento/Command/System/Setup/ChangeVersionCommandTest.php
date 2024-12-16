<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Mage_Core_Model_Resource_Resource;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ChangeVersionCommandTest extends TestCase
{
    public function testChangeVersion()
    {
        $this->markTestSkipped();

        $command = $this->getMockBuilder(ChangeVersionCommand::class)
            ->getMock();

        $resourceModel = $this->getMockBuilder(Mage_Core_Model_Resource_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(['setDbVersion', 'setDataVersion'])
            ->getMock();

        $resourceModel
            ->expects($this->once())
            ->method('setDbVersion')
            ->with('weee_setup', '1.6.0.0');

        $resourceModel
            ->expects($this->once())
            ->method('setDataVersion')
            ->with('weee_setup', '1.6.0.0');

        $application = $this->getApplication();
        $application->add($command);

        $command = $this->getApplication()->find('sys:setup:change-version');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'version'   => '1.6.0.0']);

        $this->assertStringContainsString('Successfully updated: "Mage_Weee" - "weee_setup" to version: "1.6.0.0"', $commandTester->getDisplay());
    }

    public function testUpdateBySetupName()
    {
        $this->markTestSkipped();

        $command = $this->getMockBuilder(ChangeVersionCommand::class)
            ->getMock();

        $resourceModel = $this->getMockBuilder(Mage_Core_Model_Resource_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(['setDbVersion', 'setDataVersion'])
            ->getMock();

        $command
            ->expects($this->once())
            ->method('_getResourceSingleton')
            ->willReturn($resourceModel);

        $resourceModel
            ->expects($this->once())
            ->method('setDbVersion')
            ->with('weee_setup', '1.6.0.0');

        $resourceModel
            ->expects($this->once())
            ->method('setDataVersion')
            ->with('weee_setup', '1.6.0.0');

        $application = $this->getApplication();
        $application->add($command);

        $command = $this->getApplication()->find('sys:setup:change-version');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'version'   => '1.6.0.0', 'setup'     => 'weee_setup']);

        $this->assertStringContainsString('Successfully updated: "Mage_Weee" - "weee_setup" to version: "1.6.0.0"', $commandTester->getDisplay());
    }

    public function testSetupNameNotFound()
    {
        $application = $this->getApplication();
        $application->add(new ChangeVersionCommand());

        $command = $this->getApplication()->find('sys:setup:change-version');

        $commandTester = new CommandTester($command);

        $this->expectException(
            InvalidArgumentException::class,
        );

        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'version'   => '1.6.0.0', 'setup'     => 'no_setup_exists']);
    }

    public function testModuleDoesNotExist()
    {
        $application = $this->getApplication();
        $application->add(new ChangeVersionCommand());

        $command = $this->getApplication()->find('sys:setup:change-version');

        $commandTester = new CommandTester($command);

        $this->expectException(InvalidArgumentException::class);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'I_DO_NOT_EXIST', 'version'   => '1.0.0.0']);
    }

    public function testCommandReturnsEarlyIfNoSetupResourcesForModule()
    {
        $command = $this->getMockBuilder(ChangeVersionCommand::class)
            ->setMethods(['getModuleSetupResources'])
            ->getMock();

        $command->expects($this->once())
            ->method('getModuleSetupResources')
            ->with('Mage_Weee')
            ->willReturn([]);

        $application = $this->getApplication();
        $application->add($command);

        $command = $this->getApplication()->find('sys:setup:change-version');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command'   => $command->getName(), 'module'    => 'Mage_Weee', 'version'   => '1.0.0.0', 'setup'     => 'weee_setup']);

        $this->assertStringContainsString('No setup resources found for module: "Mage_Weee"', $commandTester->getDisplay());
    }
}
