<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\TestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;

class CompareVersionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());
        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
            )
        );

        $this->assertRegExp('/Setup/', $commandTester->getDisplay());
        $this->assertRegExp('/Module/', $commandTester->getDisplay());
        $this->assertRegExp('/DB/', $commandTester->getDisplay());
        $this->assertRegExp('/Data/', $commandTester->getDisplay());
        $this->assertRegExp('/Status/', $commandTester->getDisplay());
    }

    public function testJunit()
    {
        vfsStream::setup();
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());
        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'     => $command->getName(),
                '--log-junit' => vfsStream::url('root/junit.xml'),
            )
        );

        $this->assertFileExists(vfsStream::url('root/junit.xml'));
    }
}
