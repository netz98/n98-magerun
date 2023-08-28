<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MergeJsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MergeJsCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:merge-js');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--on'     => true, 'store'    => 'admin']
        );
        self::assertMatchesRegularExpression('/JS Merging enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--off'    => true, 'store'    => 'admin']
        );

        self::assertMatchesRegularExpression('/JS Merging disabled/', $commandTester->getDisplay());
    }
}
