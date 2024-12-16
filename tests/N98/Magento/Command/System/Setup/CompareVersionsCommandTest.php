<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\TestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;

final class CompareVersionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());

        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
        );

        $this->assertMatchesRegularExpression('/Setup/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Module/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/DB/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Data/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Status/', $commandTester->getDisplay());
    }

    public function testJunit()
    {
        vfsStream::setup();
        $application = $this->getApplication();
        $application->add(new CompareVersionsCommand());

        $command = $this->getApplication()->find('sys:setup:compare-versions');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'     => $command->getName(), '--log-junit' => vfsStream::url('root/junit.xml')],
        );

        $this->assertFileExists(vfsStream::url('root/junit.xml'));
    }
}
