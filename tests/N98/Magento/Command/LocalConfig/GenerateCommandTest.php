<?php

declare(strict_types=1);

/** @noinspection SimpleXmlLoadFileUsageInspection */

namespace N98\Magento\Command\LocalConfig;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use InvalidArgumentException;
use ReflectionClass;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $configFile;

    protected function setUp(): void
    {
        $this->configFile = sprintf('%s/%s/local.xml', sys_get_temp_dir(), $this->getName());
        mkdir(dirname($this->configFile), 0777, true);
        $commandMock = $this->getMockBuilder(GenerateCommand::class)
            ->onlyMethods(['_getLocalConfigFilename'])
            ->getMock();

        $commandMock
            ->method('_getLocalConfigFilename')
            ->willReturn($this->configFile);

        $this->getApplication()->add($commandMock);

        copy(
            sprintf('%s/app/etc/local.xml.template', $this->getTestMagentoRoot()),
            sprintf('%s/local.xml.template', dirname($this->configFile)),
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
            ],
        );

        $this->assertFileExists($this->configFile);
        $this->assertStringContainsString(sprintf('local.xml file already exists in folder "%s/app/etc"', dirname($this->configFile)), $commandTester->getDisplay());
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
            ],
        );

        $this->assertStringContainsString(sprintf('File %s/local.xml.template does not exist', dirname($this->configFile)), $commandTester->getDisplay());
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
            ],
        );

        $this->assertStringContainsString(sprintf('Folder %s is not writeable', dirname($this->configFile)), $commandTester->getDisplay());

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
            ],
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertMatchesRegularExpression('/<key><!\[CDATA\[[a-f0-9]{32}\]\]><\/key>/', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
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
            ],
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
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
            ],
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[files]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[admin]]></frontName>', $fileContent);
        $this->assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
    }

    /**
     * @dataProvider requiredFieldsProvider
     * @param string $param
     * @param string $prompt
     * @param mixed $default
     */
    public function testRequiredOptionsThrowExceptionIfNotSet($param, $prompt, $default)
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

        $questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelperMock->expects($this->once())
            ->method('ask')
            ->with(
                self::isInstanceOf(InputInterface::class),
                self::isInstanceOf(StreamOutput::class),
                new Question(
                    sprintf('<question>Please enter the %s:</question> ', $prompt),
                    $default,
                ),
            )
            ->willReturn(null);

        $command->getHelperSet()->set($questionHelperMock, 'question');

        $this->expectException(InvalidArgumentException::class);

        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public function requiredFieldsProvider(): \Iterator
    {
        yield ['db-host', 'database host', ''];
        yield ['db-user', 'database username', ''];
        yield ['db-name', 'database name', ''];
        yield ['session-save', 'session save', 'files'];
        yield ['admin-frontname', 'admin frontname', 'admin'];
    }

    public function testExecuteInteractively()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $inputs = [
            ['database host', 'some-db-host', ''],
            ['database username', 'some-db-username', ''],
            ['database password', 'some-db-password', ''],
            ['database name', 'some-db-name', ''],
            ['session save', 'some-session-save', 'files'],
            ['admin frontname', 'some-admin-front-name', 'admin'],
        ];

        foreach ($inputs as $i => $input) {
            [$prompt, $returnValue, $default] = $input;
            $questionHelperMock->expects(self::at($i))
                ->method('ask')
                ->with(
                    self::isInstanceOf(InputInterface::class),
                    self::isInstanceOf(StreamOutput::class),
                    new Question(
                        sprintf('<question>Please enter the %s:</question> ', $prompt),
                        $default,
                    ),
                )
                ->willReturn($returnValue);
        }

        $command->getHelperSet()->set($questionHelperMock, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[some-db-host]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[some-db-username]]></username>', $fileContent);
        $this->assertStringContainsString('<password><![CDATA[some-db-password]]></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[some-db-name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[some-session-save]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[some-admin-front-name]]></frontName>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
    }

    public function testIfPasswordOmittedItIsWrittenBlank()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelperMock->expects($this->once())
            ->method('ask')
            ->with(
                self::isInstanceOf(InputInterface::class),
                self::isInstanceOf(StreamOutput::class),
                new Question('<question>Please enter the database password:</question> '),
            )
            ->willReturn(null);

        $command->getHelperSet()->set($questionHelperMock, 'question');

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
            ],
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[my_db_host]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertStringContainsString('<password></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);

        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
    }

    public function testCdataTagIsNotAddedIfPresentInInput()
    {
        $command = $this->getApplication()->find('local-config:generate');
        $questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelperMock->expects($this->once())
            ->method('ask')
            ->with(
                self::isInstanceOf(InputInterface::class),
                self::isInstanceOf(StreamOutput::class),
                new Question(
                    '<question>Please enter the database host:</question> ',
                ),
            )
            ->willReturn('CDATAdatabasehost');

        $command->getHelperSet()->set($questionHelperMock, 'question');

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
            ],
        );

        $this->assertFileExists($this->configFile);
        $fileContent = \file_get_contents($this->configFile);
        $this->assertStringContainsString('<host><![CDATA[CDATAdatabasehost]]></host>', $fileContent);
        $this->assertStringContainsString('<username><![CDATA[my_db_user]]></username>', $fileContent);
        $this->assertStringContainsString('<password><![CDATA[my_db_pass]]></password>', $fileContent);
        $this->assertStringContainsString('<dbname><![CDATA[my_db_name]]></dbname>', $fileContent);
        $this->assertStringContainsString('<session_save><![CDATA[my_session_save]]></session_save>', $fileContent);
        $this->assertStringContainsString('<frontName><![CDATA[my_admin_frontname]]></frontName>', $fileContent);
        $this->assertStringContainsString('<key><![CDATA[key123456789]]></key>', $fileContent);
        $xml = \simplexml_load_file($this->configFile);
        $this->assertIsNotBool($xml);
    }

    public function testWrapCdata()
    {
        $generateCommand = new GenerateCommand();
        $reflectionClass = new ReflectionClass($generateCommand);
        $reflectionMethod = $reflectionClass->getMethod('_wrapCData');
        $reflectionMethod->setAccessible(true);

        $sujet = function ($string) use ($reflectionMethod, $generateCommand) {
            return $reflectionMethod->invoke($generateCommand, $string);
        };

        $this->assertSame('', $sujet(null));
        $this->assertSame('<![CDATA[CDATA]]>', $sujet('CDATA'));
        $this->assertSame('<![CDATA[]]]]>', $sujet(']]'));
        $this->assertSame('<![CDATA[ with terminator "]]>]]&gt;<![CDATA[" inside ]]>', $sujet(' with terminator "]]>" inside '));
        $this->assertSame(']]&gt;<![CDATA[ at the start ]]>', $sujet(']]> at the start '));
        $this->assertSame('<![CDATA[ at the end ]]>]]&gt;', $sujet(' at the end ]]>'));
    }

    protected function tearDown(): void
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
