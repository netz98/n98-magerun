<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MergeCssCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MergeCssCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:merge-css');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--on'     => true, 'store'    => 'admin']
        );
        self::assertMatchesRegularExpression('/CSS Merging enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--off'    => true, 'store'    => 'admin']
        );

        self::assertMatchesRegularExpression('/CSS Merging disabled/', $commandTester->getDisplay());
    }
}
