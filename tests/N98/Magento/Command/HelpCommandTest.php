<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Tester\CommandTester;

class HelpCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getApplication()->find('help');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => 'help',
            )
        );

        $this->assertContains('The help command displays help for a given command', $commandTester->getDisplay());
    }
}
