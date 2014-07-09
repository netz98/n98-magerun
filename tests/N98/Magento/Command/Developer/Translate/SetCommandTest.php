<?php

namespace N98\Magento\Command\Developer\Translate;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class SetCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InlineAdminCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:translate:set');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'string'    => 'foo',
                'translate' => 'bar',
                'store'     => 'admin',
            )
        );
        $this->assertContains('foo => bar', $commandTester->getDisplay());
    }
}