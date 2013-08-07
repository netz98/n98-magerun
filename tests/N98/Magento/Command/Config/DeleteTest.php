<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class DeleteTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $setCommand = $this->getApplication()->find('config:set');
        $deleteCommand = $this->getApplication()->find('config:delete');

        /**
         * Add a new entry
         */
        $commandTester = new CommandTester($setCommand);
        $commandTester->execute(
            array(
                'command' => $setCommand->getName(),
                'path'    => 'n98_magerun/foo/bar',
                'value'   => '1234',
            )
        );
        $this->assertContains('n98_magerun/foo/bar => 1234', $commandTester->getDisplay());


        $commandTester = new CommandTester($deleteCommand);
        $commandTester->execute(
            array(
                'command' => $deleteCommand->getName(),
                'path'    => 'n98_magerun/foo/bar',
            )
        );
        $this->assertContains('Deleted entry scope => default path => n98_magerun/foo/bar', $commandTester->getDisplay());
    }
}
