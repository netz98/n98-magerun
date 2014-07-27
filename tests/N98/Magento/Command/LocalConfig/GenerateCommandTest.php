<?php

namespace N98\Magento\Command\LocalConfig;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class GenerateCommandTest extends TestCase
{
    public function testExecute()
    {
        $testConfigFile = __DIR__ . '/_local.xml';
        $application = $this->getApplication();
        $commandMock = $this->getMock('\N98\Magento\Command\LocalConfig\GenerateCommand', array('_getLocalConfigFilename'));
        $commandMock
            ->expects($this->any())
            ->method('_getLocalConfigFilename')
            ->will($this->returnValue($testConfigFile));
        $application->add($commandMock);
        $command = $this->getApplication()->find('local-config:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789'
            )
        );

        $this->assertFileExists($testConfigFile);
        $fileContent = \file_get_contents($testConfigFile);
        $this->assertContains('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertContains('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($testConfigFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function tearDown()
    {
        @unlink(__DIR__ . '/_local.xml');
    }
}
