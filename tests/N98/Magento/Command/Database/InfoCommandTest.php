<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class InfoCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('db:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/PDO-Connection-String/', $commandTester->getDisplay());
    }

    public function testExecuteWithSettingArgument()
    {
        $application = $this->getApplication();
        $application->add(new InfoCommand());
        $command = $this->getApplication()->find('db:info');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'setting' => 'MySQL-Cli-String',
            )
        );

        $this->assertNotRegExp('/MySQL-Cli-String/', $commandTester->getDisplay());
        $this->assertContains('mysql -h', $commandTester->getDisplay());
    }
}
