<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FlushCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        if ($application->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1) {
            $application = $this->getApplication();
            $application->add(new FlushCommand());
            $command = $this->getApplication()->find('cache:flush');

            $commandTester = new CommandTester($command);
            $commandTester->execute(array('command' => $command->getName()));

            $this->assertRegExp('/Cache cleared/', $commandTester->getDisplay());
        }
    }
}
