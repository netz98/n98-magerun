<?php

namespace N98\Magento\Command\Script\Repository;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

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
            array(
                'command' => $command->getName(),
            )
        );

        $this->assertContains('Cache Flush Command Test (Hello World)', $commandTester->getDisplay());
        $this->assertContains('Foo command', $commandTester->getDisplay());
        $this->assertContains('Bar command', $commandTester->getDisplay());
    }
}
