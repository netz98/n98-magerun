<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('sys:cron:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'job'     => 'log_clean']
        );

        self::assertMatchesRegularExpression('/Run Mage_Log_Model_Cron::logClean done/', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function urlBuildingWhileCron()
    {
        $application = $this->getApplication();
        $application->add(new RunCommand());
        $command = $this->getApplication()->find('sys:cron:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'job'     => 'log_clean']
        );

        self::assertMatchesRegularExpression('/Run Mage_Log_Model_Cron::logClean done/', $commandTester->getDisplay());
    }
}
