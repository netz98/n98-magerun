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
            array(
                'command' => $command->getName(),
                '--tags'  => true,
                '--mtime' => true,
            )
        );

        self::assertRegExp('/ID/', $commandTester->getDisplay());
        self::assertRegExp('/EXPIRE/', $commandTester->getDisplay());
        self::assertRegExp('/MTIME/', $commandTester->getDisplay());
        self::assertRegExp('/TAGS/', $commandTester->getDisplay());
    }
}
