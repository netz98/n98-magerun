<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ReportCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('cache:report');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), '--tags'  => true, '--mtime' => true],
        );

        $this->assertMatchesRegularExpression('/ID/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/EXPIRE/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/MTIME/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/TAGS/', $commandTester->getDisplay());
    }
}
