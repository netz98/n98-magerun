<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BaseUrlListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new BaseUrlListCommand());

        $command = $this->getApplication()->find('sys:store:config:base-url:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
        );

        $this->assertMatchesRegularExpression('/secure_baseurl/', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/unsecure_baseurl/', $commandTester->getDisplay());
    }
}
