<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class EnableCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
            $application->add(new EnableCommand());
            $command = $this->getApplication()->find('cache:enable');

            $commandTester = new CommandTester($command);
            $commandTester->execute(['command' => $command->getName()]);

            self::assertMatchesRegularExpression('/Caches enabled/', $commandTester->getDisplay());
        }
    }

    public function testExecuteMultipleCaches()
    {
        $application = $this->getApplication();
        if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
            $application->add(new DisableCommand());

            $command = $this->getApplication()->find('cache:enable');
            $commandTester = new CommandTester($command);
            $commandTester->execute(
                ['command' => $command->getName(), 'code'    => 'eav,config']
            );

            self::assertMatchesRegularExpression('/Cache config enabled/', $commandTester->getDisplay());
            self::assertMatchesRegularExpression('/Cache eav enabled/', $commandTester->getDisplay());
        }
    }
}
