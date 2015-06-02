<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class HelpCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getApplication()->find('help');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => 'help'
            )
        );
    
        $this->assertContains('The help command displays help for a given command', $commandTester->getDisplay());
    }
}
