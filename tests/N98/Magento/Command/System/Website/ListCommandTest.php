<?php

namespace N98\Magento\Command\System\Website;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('sys:website:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertRegExp('/Magento Websites/', $commandTester->getDisplay());
        self::assertRegExp('/id/', $commandTester->getDisplay());
        self::assertRegExp('/code/', $commandTester->getDisplay());
    }
}
