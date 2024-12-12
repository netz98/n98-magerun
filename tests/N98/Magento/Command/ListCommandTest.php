<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getApplication()->find('list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => 'list'],
        );

        $this->assertStringContainsString(sprintf('n98-magerun %s by valantic CEC', $this->getApplication()->getVersion()), $commandTester->getDisplay());
    }
}
