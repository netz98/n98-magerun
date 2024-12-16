<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends TestCase
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
            ['command' => $command->getName()],
        );

        $this->assertStringContainsString('Cache Flush Command Test (Hello World)', $commandTester->getDisplay());
        $this->assertStringContainsString('Foo command', $commandTester->getDisplay());
        $this->assertStringContainsString('Bar command', $commandTester->getDisplay());
    }
}
