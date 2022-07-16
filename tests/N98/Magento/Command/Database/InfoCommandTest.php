<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('db:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertRegExp('/PDO-Connection-String/', $commandTester->getDisplay());
    }

    public function testExecuteWithSettingArgument()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('db:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'setting' => 'MySQL-Cli-String']
        );

        self::assertNotRegExp('/MySQL-Cli-String/', $commandTester->getDisplay());
        self::assertStringContainsString('mysql -h', $commandTester->getDisplay());
    }
}
