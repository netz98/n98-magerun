<?php

/** @noinspection SimpleXmlLoadFileUsageInspection */

namespace N98\Magento\Command\LocalConfig;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\StreamOutput;
use InvalidArgumentException;
use ReflectionClass;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $configFile;

    public function setUp(): void
    {
        $this->configFile = sprintf('%s/%s/local.xml', sys_get_temp_dir(), $this->getName());
        mkdir(dirname($this->configFile), 0777, true);
        $commandMock = $this->getMockBuilder(GenerateCommand::class)
            ->setMethods(['_getLocalConfigFilename'])
            ->getMock();

        $commandMock
            ->method('_getLocalConfigFilename')
            ->willReturn($this->configFile);

        $this->getApplication()->add($commandMock);

        copy(
            sprintf('%s/app/etc/local.xml.template', $this->getTestMagentoRoot()),
            sprintf('%s/local.xml.template', dirname($this->configFile))
        );

        parent::setUp();
    }

    public function testErrorIsPrintedIfConfigFileExists()
    {
        touch($this->configFile);
        $command = $this->getApplication()->find('local-config:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertFileExists($this->configFile);
        self::assertStringContainsString(
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
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertStringContainsString(
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
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertStringContainsString(
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
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
            ]
        );

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        self::assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        self::assertRegExp('/<key><!\[CDATA\[[a-f0-9]{32}\]\]><\/key>/', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    public function testExecuteWithCliParameters()
    {
        $command = $this->getApplication()->find('local-config:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        self::assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        self::assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    public function testInteractiveInputUsesDefaultValuesIfNoValueEntered()
    {
        $command = $this->getApplication()->find('local-config:generate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'encryption-key'  => 'key123456789',
            ],
            [
                'interactive' => false,
            ]
        );

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        self::assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[files]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[admin]]></frontName>', $fileContent);
        self::assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    /**
     * @dataProvider requiredFieldsProvider
     * @param string $param
     * @param string $prompt
     */
    public function testRequiredOptionsThrowExceptionIfNotSet($param, $prompt)
    {
        $command = $this->getApplication()->find('local-config:generate');

        $options = [
            'command'         => $command->getName(),
            'db-host'         => 'my_db_host',
            'db-user'         => 'my_db_user',
            'db-pass'         => 'my_db_pass',
            'db-name'         => 'my_db_name',
            'session-save'    => 'my_session_save',
            'admin-frontname' => 'my_admin_frontname',
            'encryption-key'  => 'key123456789',
        ];

        unset($options[$param]);

        $dialog = $this->getMockBuilder(DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('ask')
            ->with(
                self::isInstanceOf(StreamOutput::class),
                sprintf('<question>Please enter the %s:</question> ', $prompt)
            )
            ->willReturn(null);

        $command->getHelperSet()->set($dialog, 'dialog');

        $this->expectException(InvalidArgumentException::class);

        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }

    /**
     * @return array
     */
    public function requiredFieldsProvider()
    {
        return [
            ['db-host', 'database host'],
            ['db-user', 'database username'],
            ['db-name', 'database name'],
            ['session-save', 'session save'],
            ['admin-frontname', 'admin frontname'],
        ];
    }

    public function testExecuteInteractively()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMockBuilder(DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $inputs = [
            ['database host', 'some-db-host'],
            ['database username', 'some-db-username'],
            ['database password', 'some-db-password'],
            ['database name', 'some-db-name'],
            ['session save', 'some-session-save'],
            ['admin frontname', 'some-admin-front-name'],
        ];

        foreach ($inputs as $i => $input) {
            [$prompt, $returnValue] = $input;
            $dialog->expects(self::at($i))
                ->method('ask')
                ->with(
                    self::isInstanceOf(StreamOutput::class),
                    sprintf('<question>Please enter the %s:</question> ', $prompt)
                )
                ->willReturn($returnValue);
        }

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[some-db-host]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[some-db-username]]></username>', $fileContent);
        self::assertStringContainsString('<password><![CDATA[some-db-password]]></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[some-db-name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[some-session-save]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[some-admin-front-name]]></frontName>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    public function testIfPasswordOmittedItIsWrittenBlank()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMockBuilder(DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('ask')
            ->with(
                self::isInstanceOf(StreamOutput::class),
                sprintf('<question>Please enter the database password:</question> ')
            )
            ->willReturn(null);

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'         => $command->getName(),
                'db-host'         => 'my_db_host',
                'db-user'         => 'my_db_user',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        self::assertStringContainsString('<password></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        self::assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    public function testCdataTagIsNotAddedIfPresentInInput()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $dialog = $this->getMockBuilder(DialogHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        $dialog->expects(self::once())
            ->method('ask')
            ->with(
                self::isInstanceOf(StreamOutput::class),
                '<question>Please enter the database host:</question> '
            )
            ->willReturn('CDATAdatabasehost');

        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'         => $command->getName(),
                'db-user'         => 'my_db_user',
                'db-pass'         => 'my_db_pass',
                'db-name'         => 'my_db_name',
                'session-save'    => 'my_session_save',
                'admin-frontname' => 'my_admin_frontname',
                'encryption-key'  => 'key123456789',
            ]
        );

        self::assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        self::assertStringContainsString('<host><![CDATA[CDATAdatabasehost]]></host>', $fileContent);
        self::assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        self::assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        self::assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        self::assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        self::assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        self::assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);
        $xml = \simplexml_load_file($this->configFile);
        self::assertIsNotBool($xml);
    }

    /**
     * @test unit utility method _wrapCdata
     */
    public function wrapCdata()
    {
        $command = new GenerateCommand();
        $refl = new ReflectionClass($command);
        $method = $refl->getMethod('_wrapCData');
        $method->setAccessible(true);
        $sujet = function ($string) use ($method, $command) {
            return $method->invoke($command, $string);
        };

        self::assertSame('', $sujet(null));
        self::assertSame('<![CDATA[CDATA]]>', $sujet('CDATA'));
        self::assertSame('<![CDATA[]]]]>', $sujet(']]'));
        self::assertSame('<![CDATA[ with terminator "]]>]]&gt;<![CDATA[" inside ]]>', $sujet(' with terminator "]]>" inside '));
        self::assertSame(']]&gt;<![CDATA[ at the start ]]>', $sujet(']]> at the start '));
        self::assertSame('<![CDATA[ at the end ]]>]]&gt;', $sujet(' at the end ]]>'));
    }

    public function tearDown(): void
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
