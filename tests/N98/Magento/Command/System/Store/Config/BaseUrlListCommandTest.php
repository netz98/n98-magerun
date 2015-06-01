<?php

namespace N98\Magento\Command\System\Store\Config;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class BaseUrlListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new BaseUrlListCommand());
        $command = $this->getApplication()->find('sys:store:config:base-url:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName()
            )
        );
    
        $this->assertRegExp('/secure_baseurl/', $commandTester->getDisplay());
        $this->assertRegExp('/unsecure_baseurl/', $commandTester->getDisplay());
    }
}
