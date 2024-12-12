<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Observer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('dev:module:observer:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'type'    => 'global'],
        );

        $this->assertStringContainsString('controller_front_init_routers', $commandTester->getDisplay());
    }
}
