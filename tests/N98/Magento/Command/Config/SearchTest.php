<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class SearchTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('config:search');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'text'      => 'This message will be shown',
            )
        );
        $this->assertContains('Found a field with a match', $commandTester->getDisplay());        
    }
}
