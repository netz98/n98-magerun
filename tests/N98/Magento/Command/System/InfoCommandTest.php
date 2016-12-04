<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('sys:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Magento System Information/', $commandTester->getDisplay());
        $this->assertRegExp('/Install Date/', $commandTester->getDisplay());
        $this->assertRegExp('/Crypt Key/', $commandTester->getDisplay());

        // Settings argument
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'key'     => 'version',
            )
        );
        $this->assertRegExp('/\d+\.\d+\.\d+\.\d+/', $commandTester->getDisplay());
    }
}
