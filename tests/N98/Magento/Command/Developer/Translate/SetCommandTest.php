<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SetCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InlineAdminCommand());
        $application->setAutoExit(false);

        $command = $this->getApplication()->find('dev:translate:set');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'string'    => 'foo', 'translate' => 'bar', 'store'     => 'admin'],
        );
        $this->assertStringContainsString('foo => bar', $commandTester->getDisplay());
    }
}
