<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        $config['script']['folders'][] = __DIR__ . '/_scripts';
        $application->setConfig($config);

        $application->add(new RunCommand());
        $command = $this->getApplication()->find('script:repo:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()]
        );

        self::assertStringContainsString('Cache Flush Command Test (Hello World)', $commandTester->getDisplay());
        self::assertStringContainsString('Foo command', $commandTester->getDisplay());
        self::assertStringContainsString('Bar command', $commandTester->getDisplay());
    }
}
