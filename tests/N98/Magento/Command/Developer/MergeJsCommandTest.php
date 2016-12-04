<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MergeJsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MergeJsCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:merge-js');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--on'     => true,
                'store'    => 'admin',
            )
        );
        $this->assertRegExp('/JS Merging enabled/', $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--off'    => true,
                'store'    => 'admin',
            )
        );

        $this->assertRegExp('/JS Merging disabled/', $commandTester->getDisplay());
    }
}
