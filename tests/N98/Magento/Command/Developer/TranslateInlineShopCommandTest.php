<?php

namespace N98\Magento\Command\Developer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class TranslateInlineShopCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new TranslateInlineAdminCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:translate:shop');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                'store'    => 'admin',
                '--on'     => true,
            )
        );
        $this->assertContains('Inline Translation enabled', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                'store'    => 'admin',
                '--off'    => true,
            )
        );

        $this->assertContains('Inline Translation disabled', $commandTester->getDisplay());
    }
}