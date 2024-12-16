<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ProfilerCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ProfilerCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:profiler');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--on'     => true]
        );
        self::assertMatchesRegularExpression('/Profiler enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--off'    => true]
        );

        self::assertMatchesRegularExpression('/Profiler disabled/', $commandTester->getDisplay());
    }
}
