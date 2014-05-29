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
                'command'           => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
            )
        );

        $this->assertFileExists($testConfigFile);
        $fileContent = \file_get_contents($testConfigFile);
        $this->assertContains('<![CDATA[my_db_host]]>', $fileContent);
        $this->assertContains('<![CDATA[my_db_user]]>', $fileContent);
        $this->assertContains('<![CDATA[my_db_pass]]>', $fileContent);
        $this->assertContains('<![CDATA[my_db_name]]>', $fileContent);
        $this->assertContains('<![CDATA[my_session_save]]>', $fileContent);
        $this->assertContains('<![CDATA[my_admin_frontname]]>', $fileContent);

        $xml = \simplexml_load_file($testConfigFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function tearDown()
    {
        @unlink(__DIR__ . '/_local.xml');
    }
}