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
            ['command' => $command->getName()]
        );

        self::assertMatchesRegularExpression('/Setup/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/Module/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/DB/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/Data/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/Status/', $commandTester->getDisplay());
    }

    public function testJunit()
    {
        vfsStream::setup();
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());
        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'     => $command->getName(), '--log-junit' => vfsStream::url('root/junit.xml')]
        );

        self::assertFileExists(vfsStream::url('root/junit.xml'));
    }
}
