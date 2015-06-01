<?php

namespace N98\Magento\Command\Developer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class TemplateHintsBlocksCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new TemplateHintsBlocksCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:template-hints-blocks');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--on'     => true,
                'store'    => 'admin',
            )
        );
        $this->assertRegExp('/Template Hints Blocks enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--off'    => true,
                'store'    => 'admin',
            )
        );

        $this->assertRegExp('/Template Hints Blocks disabled/', $commandTester->getDisplay());
    }
}
