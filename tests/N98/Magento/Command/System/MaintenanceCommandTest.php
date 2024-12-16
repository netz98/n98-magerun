<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MaintenanceCommandTest extends TestCase
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
            ['command' => $command->getName(), '--on'    => ''],
        );
        $this->assertMatchesRegularExpression('/Maintenance mode on/', $commandTester->getDisplay());
        $this->assertFileExists($magentoRootFolder . '/maintenance.flag');

        $commandTester->execute(
            ['command' => $command->getName(), '--off'   => ''],
        );
        $this->assertMatchesRegularExpression('/Maintenance mode off/', $commandTester->getDisplay());
        $this->assertFileDoesNotExist($magentoRootFolder . '/maintenance.flag');
    }
}
