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

        $this->assertRegExp('/ID/', $commandTester->getDisplay());
        $this->assertRegExp('/EXPIRE/', $commandTester->getDisplay());
        $this->assertRegExp('/MTIME/', $commandTester->getDisplay());
        $this->assertRegExp('/TAGS/', $commandTester->getDisplay());
    }
}
