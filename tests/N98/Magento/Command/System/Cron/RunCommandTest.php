<?php

namespace N98\Magento\Command\System\Cron;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class RunCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('sys:cron:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'job'     => 'log_clean')
        );
    
        $this->assertRegExp('/Run Mage_Log_Model_Cron::logClean done/', $commandTester->getDisplay());
    }
}
