<?php

namespace N98\Magento\Command\Developer\Setup\Script;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ProfilerCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new AttributeCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:setup:script:attribute');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                 'command'  => $command->getName(),
                 'code'     => 'name',
            )
        );
        $this->assertContains("'label' => 'Name',", $commandTester->getDisplay());
    }
}