<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DisableCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
            $application->add(new DisableCommand());

            $command = $this->getApplication()->find('cache:disable');
            $commandTester = new CommandTester($command);
            $commandTester->execute(['command' => $command->getName()]);

            self::assertRegExp('/Caches disabled/', $commandTester->getDisplay());
        }
    }

    public function testExecuteMultipleCaches()
    {
        $application = $this->getApplication();
        if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
            $application->add(new DisableCommand());

            $command = $this->getApplication()->find('cache:disable');
            $commandTester = new CommandTester($command);
            $commandTester->execute(
                ['command' => $command->getName(), 'code'    => 'eav,config']
            );

            self::assertRegExp('/Cache config disabled/', $commandTester->getDisplay());
            self::assertRegExp('/Cache eav disabled/', $commandTester->getDisplay());
        }
    }
}
