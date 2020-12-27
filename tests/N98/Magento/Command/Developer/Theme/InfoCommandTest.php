<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:theme:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
            )
        );

        self::assertContains('base/default', $commandTester->getDisplay());
        self::assertContains('Design Package Name', $commandTester->getDisplay());
    }
}
