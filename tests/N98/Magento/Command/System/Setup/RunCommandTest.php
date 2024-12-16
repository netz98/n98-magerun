<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RunCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());

        $command = $this->getApplication()->find('sys:setup:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
        );

        $this->assertMatchesRegularExpression('/done/', $commandTester->getDisplay());
    }
}
