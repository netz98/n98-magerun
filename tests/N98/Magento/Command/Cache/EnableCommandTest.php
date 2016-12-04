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
            $commandTester->execute(array('command' => $command->getName()));

            $this->assertRegExp('/Caches enabled/', $commandTester->getDisplay());
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
                array(
                    'command' => $command->getName(),
                    'code'    => 'eav,config',
                )
            );

            $this->assertRegExp('/Cache config enabled/', $commandTester->getDisplay());
            $this->assertRegExp('/Cache eav enabled/', $commandTester->getDisplay());
        }
    }
}
