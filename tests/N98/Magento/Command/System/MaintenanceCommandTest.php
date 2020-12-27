<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MaintenanceCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MaintenanceCommand());
        $command = $application->find('sys:maintenance');

        $magentoRootFolder = $application->getMagentoRootFolder();
        if (!is_writable($magentoRootFolder)) {
            self::markTestSkipped('Magento root folder must be writable.');
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--on'    => '',
            )
        );
        self::assertRegExp('/Maintenance mode on/', $commandTester->getDisplay());
        self::assertFileExists($magentoRootFolder . '/maintenance.flag');

        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--off'   => '',
            )
        );
        self::assertRegExp('/Maintenance mode off/', $commandTester->getDisplay());
        self::assertFileNotExists($magentoRootFolder . '/maintenance.flag');
    }
}
