<?php

namespace N98\Magento\Command\Developer\Module\Observer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:module:observer:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'type'    => 'global',
            )
        );
    
        $this->assertContains('controller_front_init_routers', $commandTester->getDisplay());
    }
}
