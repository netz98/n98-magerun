<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

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
            ['command'  => $command->getName(), '--on'     => true, 'store'    => 'admin']
        );
        self::assertMatchesRegularExpression('/Template Hints Blocks enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--off'    => true, 'store'    => 'admin']
        );

        self::assertMatchesRegularExpression('/Template Hints Blocks disabled/', $commandTester->getDisplay());
    }
}
