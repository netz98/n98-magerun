<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SymlinksCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new SymlinksCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:symlinks');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--on'     => true]
        );
        self::assertRegExp('/Symlinks allowed/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--off'    => true]
        );

        self::assertRegExp('/Symlinks denied/', $commandTester->getDisplay());
    }
}
