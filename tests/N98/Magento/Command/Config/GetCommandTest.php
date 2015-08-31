<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class GetCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $setCommand = $this->getApplication()->find('config:set');
        $getCommand = $this->getApplication()->find('config:get');

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

        $commandTester = new CommandTester($getCommand);
        $commandTester->execute(
            array(
                 'command' => $getCommand->getName(),
                 'path'    => 'n98_magerun/foo/bar',
            )
        );
        $this->assertContains('| n98_magerun/foo/bar | default | 0        | 1234  |', $commandTester->getDisplay());

        $commandTester->execute(
            array(
                 'command'         => $getCommand->getName(),
                 'path'            => 'n98_magerun/foo/bar',
                 '--update-script' => true
            )
        );
        $this->assertContains(
            "\$installer->setConfigData('n98_magerun/foo/bar', '1234');",
            $commandTester->getDisplay()
        );

        $commandTester->execute(
            array(
                 'command'          => $getCommand->getName(),
                 'path'             => 'n98_magerun/foo/bar',
                 '--magerun-script' => true
            )
        );
        $this->assertContains(
            "config:set n98_magerun/foo/bar --scope-id=0 --scope=default " . escapeshellarg(1234),
            $commandTester->getDisplay()
        );

        /**
         * Dump CSV
         */
        $commandTester->execute(
            array(
                'command'  => $getCommand->getName(),
                'path'     => 'n98_magerun/foo/bar',
                '--format' => 'csv',
            )
        );
        $this->assertContains('Path,Scope,Scope-ID,Value', $commandTester->getDisplay());
        $this->assertContains('n98_magerun/foo/bar,default,0,1234', $commandTester->getDisplay());

        /**
         * Dump XML
         */
        $commandTester->execute(
            array(
                'command'  => $getCommand->getName(),
                'path'     => 'n98_magerun/foo/bar',
                '--format' => 'xml',
            )
        );
        $this->assertContains('<table>', $commandTester->getDisplay());
        $this->assertContains('<Value>1234</Value>', $commandTester->getDisplay());

        /**
         * Dump XML
         */
        $commandTester->execute(
            array(
                'command'  => $getCommand->getName(),
                'path'     => 'n98_magerun/foo/bar',
                '--format' => 'json',
            )
        );
        $this->assertRegExp('/"Value":\s*"1234"/', $commandTester->getDisplay());
    }

}
