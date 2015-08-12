<?php

namespace N98\Magento\Command\Developer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class TemplateHintsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new TemplateHintsCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:template-hints');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--on'     => true,
                'store'    => 'admin',
            )
        );
        $this->assertRegExp('/Template Hints enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--off'    => true,
                'store'    => 'admin',
            )
        );

        $this->assertRegExp('/Template Hints disabled/', $commandTester->getDisplay());
    }
}
