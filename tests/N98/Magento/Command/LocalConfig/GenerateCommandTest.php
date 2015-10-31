<?php

namespace N98\Magento\Command\LocalConfig;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class GenerateCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $configFile;

    public function setUp()
    {
        $this->configFile = sprintf('%s/%s/local.xml', sys_get_temp_dir(), $this->getName());
        mkdir(dirname($this->configFile), 0777, true);
        $commandMock = $this->getMock(
            '\N98\Magento\Command\LocalConfig\GenerateCommand',
            array('_getLocalConfigFilename')
        );

        $commandMock
            ->expects($this->any())
            ->method('_getLocalConfigFilename')
            ->will($this->returnValue($this->configFile));

        $this->getApplication()->add($commandMock);

        copy(
            sprintf('%s/app/etc/local.xml.template', $this->getTestMagentoRoot()),
            sprintf('%s/local.xml.template', dirname($this->configFile))
        );
    }

    public function testErrorIsPrintedIfConfigFileExists()
    {
        touch($this->configFile);
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

        $this->assertFileExists($this->configFile);
        $this->assertContains(
            sprintf('local.xml file already exists in folder "%s/app/etc"', dirname($this->configFile)),
            $commandTester->getDisplay()
        );
    }

    public function testErrorIsPrintedIfConfigTemplateNotExists()
    {
        unlink(sprintf('%s/local.xml.template', dirname($this->configFile)));
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

        $this->assertContains(
            sprintf('File %s/local.xml.template does not exist', dirname($this->configFile)),
            $commandTester->getDisplay()
        );
    }

    public function testErrorIsPrintedIfAppEtcDirNotWriteable()
    {
        $command = $this->getApplication()->find('local-config:generate');

        $originalMode = fileperms(dirname($this->configFile));
        chmod(dirname($this->configFile), 0544);

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

        $this->assertContains(
            sprintf('Folder %s is not writeable', dirname($this->configFile)),
            $commandTester->getDisplay()
        );

        chmod(dirname($this->configFile), $originalMode);
    }

    public function testRandomMd5IsUsedIfNoEncryptionKeyParamPassed()
    {
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
                'admin-frontname' => 'my_admin_frontname'
            )
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertRegExp('/<key><!\[CDATA\[[a-f0-9]{32}\]\]><\/key>/', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function testExecuteWithCliParameters()
    {
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

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertContains('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function testInteractiveInputUsesDefaultValuesIfNoValueEntered()
    {
        $command = $this->getApplication()->find('local-config:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'encryption-key'  => 'key123456789'
            ),
            array(
                'interactive' => false
            )
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[files]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[admin]]></frontName>', $fileContent);
        $this->assertContains('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    /**
     * @dataProvider requiredFieldsProvider
     * @param string $param
     * @param string $prompt
     */
    public function testRequiredOptionsThrowExceptionIfNotSet($param, $prompt)
    {
        $command = $this->getApplication()->find('local-config:generate');

        $options = array(
            'command'         => $command->getName(),
            'db-host'         => 'my_db_host',
            'db-user'         => 'my_db_user',
            'db-pass'         => 'my_db_pass',
            'db-name'         => 'my_db_name',
            'session-save'    => 'my_session_save',
            'admin-frontname' => 'my_admin_frontname',
            'encryption-key'  => 'key123456789'
        );

        unset($options[$param]);

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->once())
            ->method('ask')
            ->with(
                $this->isInstanceOf('Symfony\Component\Console\Output\StreamOutput'),
                sprintf('<question>Please enter the %s:</question>', $prompt)
            )
            ->will($this->returnValue(null));

        $command->getHelperSet()->set($dialog, 'dialog');

        $this->setExpectedException('InvalidArgumentException', sprintf('%s was not set', $param));

        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }

    /**
     * @return array
     */
    public function requiredFieldsProvider()
    {
        return array(
            array('db-host', 'database host'),
            array('db-user', 'database username'),
            array('db-name', 'database name'),
            array('session-save', 'session save'),
            array('admin-frontname', 'admin frontname'),
        );
    }

    public function testExecuteInteractively()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));

        $inputs = array(
            array('database host', 'some-db-host'),
            array('database username', 'some-db-username'),
            array('database password', 'some-db-password'),
            array('database name', 'some-db-name'),
            array('session save', 'some-session-save'),
            array('admin frontname', 'some-admin-front-name'),
        );

        foreach ($inputs as $i => $input) {
            list($prompt, $returnValue) = $input;
            $dialog->expects($this->at($i))
                ->method('ask')
                ->with(
                    $this->isInstanceOf('Symfony\Component\Console\Output\StreamOutput'),
                    sprintf('<question>Please enter the %s:</question>', $prompt)
                )
                ->will($this->returnValue($returnValue));
        }

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[some-db-host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[some-db-username]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[some-db-password]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[some-db-name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[some-session-save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[some-admin-front-name]]></frontName>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function testIfPasswordOmittedItIsWrittenBlank()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->once())
            ->method('ask')
            ->with(
                $this->isInstanceOf('Symfony\Component\Console\Output\StreamOutput'),
                sprintf('<question>Please enter the database password:</question>')
            )
            ->will($this->returnValue(null));

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789'
            )
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertContains('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    public function testCdataTagIsNotAddedIfPresentInInput()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));

        $dialog->expects($this->once())
            ->method('ask')
            ->with(
                $this->isInstanceOf('Symfony\Component\Console\Output\StreamOutput'),
                '<question>Please enter the database host:</question>'
            )
            ->will($this->returnValue('CDATAdatabasehost'));

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'         => $command->getName(),
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789'
            )
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertContains('<host><![CDATA[CDATAdatabasehost]]></host>', $fileContent);
        $this->assertContains('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertContains('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertContains('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertContains('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertContains('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertContains('<key><![CDATA[key123456789]]></key>', $fileContent);
        $xml = \simplexml_load_file($this->configFile);
        $this->assertNotInternalType('bool', $xml);
    }

    /**
     * @test unit utility method _wrapCdata
     */
    public function wrapCdata()
    {
        $command = new GenerateCommand();
        $refl = new \ReflectionClass($command);
        $method = $refl->getMethod('_wrapCData');
        $method->setAccessible(true);
        $sujet = function($string) use ($method, $command) {
            return $method->invoke($command, $string);
        };

        $this->assertSame('', $sujet(null));
        $this->assertSame('<![CDATA[CDATA]]>', $sujet('CDATA'));
        $this->assertSame('<![CDATA[]]]]>', $sujet(']]'));
        $this->assertSame('<![CDATA[ with terminator "]]>]]&gt;<![CDATA[" inside ]]>', $sujet(' with terminator "]]>" inside '));
        $this->assertSame(']]&gt;<![CDATA[ at the start ]]>', $sujet(']]> at the start '));
        $this->assertSame('<![CDATA[ at the end ]]>]]&gt;', $sujet(' at the end ]]>'));
    }

    public function tearDown()
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }

        if (file_exists(sprintf('%s/local.xml.template', dirname($this->configFile)))) {
            unlink(sprintf('%s/local.xml.template', dirname($this->configFile)));
        }
        rmdir(dirname($this->configFile));
    }
}
