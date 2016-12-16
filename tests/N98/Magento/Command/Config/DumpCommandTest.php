<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DumpCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('config:dump');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'xpath'     => 'global/install',
            )
        );
        $this->assertContains('date', $commandTester->getDisplay());
    }
}
