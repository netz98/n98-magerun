<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:module:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
            )
        );

        $this->assertRegExp('/Mage_Core/', $commandTester->getDisplay());
    }
}
