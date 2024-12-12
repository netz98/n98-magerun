<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('dev:theme:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
        );

        $this->assertStringContainsString('base/default', $commandTester->getDisplay());
        $this->assertStringContainsString('Design Package Name', $commandTester->getDisplay());
    }
}
