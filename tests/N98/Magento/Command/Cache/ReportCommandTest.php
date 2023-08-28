<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ReportCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:report');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), '--tags'  => true, '--mtime' => true]
        );

        self::assertMatchesRegularExpression('/ID/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/EXPIRE/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/MTIME/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/TAGS/', $commandTester->getDisplay());
    }
}
