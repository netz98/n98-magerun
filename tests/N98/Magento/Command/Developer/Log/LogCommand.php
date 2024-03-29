<?php

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LogCommand extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new LogCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:log');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--on'     => true]
        );
        self::assertMatchesRegularExpression('/Development Log/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--off'    => true]
        );

        self::assertMatchesRegularExpression('/Development Log/', $commandTester->getDisplay());
    }
}
