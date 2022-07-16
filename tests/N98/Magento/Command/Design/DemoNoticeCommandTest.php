<?php

namespace N98\Magento\Command\Design;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DemoNoticeCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DemoNoticeCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('design:demo-notice');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), 'store'    => 'admin', '--on'     => true]
        );
        self::assertRegExp('/Demo Notice enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), 'store'    => 'admin', '--off'    => true]
        );

        self::assertRegExp('/Demo Notice disabled/', $commandTester->getDisplay());
    }
}
