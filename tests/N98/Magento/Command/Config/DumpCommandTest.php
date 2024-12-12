<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DumpCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());

        $command = $this->getApplication()->find('config:dump');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'xpath'     => 'global/install'],
        );
        $this->assertStringContainsString('date', $commandTester->getDisplay());
    }
}
