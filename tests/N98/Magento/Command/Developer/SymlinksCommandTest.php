<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SymlinksCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new SymlinksCommand());
        $application->setAutoExit(false);

        $command = $this->getApplication()->find('dev:symlinks');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--on'     => true],
        );
        $this->assertMatchesRegularExpression('/Symlinks allowed/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--global' => true, '--off'    => true],
        );

        $this->assertMatchesRegularExpression('/Symlinks denied/', $commandTester->getDisplay());
    }
}
