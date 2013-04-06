<?php

namespace N98\Magento\Command\Developer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class TranslateInlineAdminCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new TranslateInlineAdminCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:translate:admin');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--on'     => true,
            )
        );
        $this->assertContains('Inline Translation (Admin) enabled', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--off'    => true,
            )
        );

        $this->assertContains('Inline Translation (Admin) disabled', $commandTester->getDisplay());
    }
}