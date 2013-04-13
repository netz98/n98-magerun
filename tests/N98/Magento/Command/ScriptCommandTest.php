<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ScriptCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ScriptCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('script');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'filename'  => __DIR__ . '/_files/test.mr',
            )
        );
        $this->assertContains('code', $commandTester->getDisplay());
        $this->assertContains('Magento Websites', $commandTester->getDisplay());
    }
}
