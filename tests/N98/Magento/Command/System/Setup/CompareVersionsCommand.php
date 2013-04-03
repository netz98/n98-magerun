<?php

namespace N98\Magento\Command\System\Setup;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class CompareVersionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());
        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );
    
        $this->assertRegExp('/Setup/', $commandTester->getDisplay());
        $this->assertRegExp('/Status/', $commandTester->getDisplay());
    }
}