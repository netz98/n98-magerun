<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InlineShopCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InlineAdminCommand());
        $application->setAutoExit(false);

        $command = $this->getApplication()->find('dev:translate:shop');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), 'store'    => 'admin', '--on'     => true],
        );
        $this->assertStringContainsString('Inline Translation enabled', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), 'store'    => 'admin', '--off'    => true],
        );

        $this->assertStringContainsString('Inline Translation disabled', $commandTester->getDisplay());
    }
}
