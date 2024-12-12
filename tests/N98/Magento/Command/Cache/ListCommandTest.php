<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('cache:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertMatchesRegularExpression('/config/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/collections/', $commandTester->getDisplay());
    }
}
