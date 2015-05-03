<?php

namespace N98\Magento\Command\Developer\Theme;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:theme:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );
    
        $this->assertContains('base/default', $commandTester->getDisplay());
        $this->assertContains('Design Package Name', $commandTester->getDisplay());
    }
}