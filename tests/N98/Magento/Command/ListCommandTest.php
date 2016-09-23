<?php

namespace N98\Magento\Command;

use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getApplication()->find('list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => 'list',
            ]
        );

        $this->assertContains(
            sprintf('n98-magerun version %s by netz98 GmbH', $this->getApplication()->getVersion()),
            $commandTester->getDisplay()
        );
    }
}
