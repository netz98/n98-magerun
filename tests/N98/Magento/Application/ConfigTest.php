<?php

declare(strict_types=1);

/*
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use N98\Magento\Command\Config\GetCommand;
use N98\Magento\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Composer\Autoload\ClassLoader;
use ErrorException;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConfigTest
 *
 * @covers  N98\Magento\Application\Config
 * @package N98\Magento\Application
 */
final class ConfigTest extends TestCase
{
    public function testCreation()
    {
        $config = new Config();
        $this->assertInstanceOf(__NAMESPACE__ . '\\Config', $config);
    }

    public function testLoader()
    {
        $config = new Config();

        try {
            $config->load();
            self::fail('An expected exception was not thrown');
        } catch (ErrorException $errorException) {
            $this->assertSame('Configuration not yet fully loaded', $errorException->getMessage());
        }

        $this->assertSame([], $config->getConfig());

        $configurationLoader = $config->getLoader();
        $this->assertInstanceOf(__NAMESPACE__ . '\\ConfigurationLoader', $configurationLoader);
        $this->assertSame($configurationLoader, $config->getLoader());

        $configurationLoader->loadStageTwo('');
        $config->load();

        $this->assertIsArray($config->getConfig());
        $this->assertGreaterThan(4, count($config->getConfig()));

        $config->setLoader($configurationLoader);
    }

    /**
     * config array setter is used in some tests on @see \N98\Magento\Application::setConfig()
     */
    public function testSetConfig()
    {
        $config = new Config();
        $config->setConfig([0, 1, 2]);

        $actual = $config->getConfig();
        $this->assertSame(1, $actual[1]);
    }

    public function testConfigCommandAlias()
    {
        $config = new Config();
        $input = new ArgvInput();
        $actual = $config->checkConfigCommandAlias($input);
        $this->assertInstanceOf(InputInterface::class, $actual);

        $saved = $_SERVER['argv'];
        $config->setConfig(['commands' => ['aliases' => [['list-help' => 'list --help']]]]);
        $inputDefinition = new InputDefinition();
        $inputDefinition->addArgument(new InputArgument('command'));

        $argv = ['/path/to/command', 'list-help'];
        $_SERVER['argv'] = $argv;
        $input = new ArgvInput($argv, $inputDefinition);
        $this->assertSame('list-help', (string) $input);
        $actual = $config->checkConfigCommandAlias($input);
        $this->assertSame('list-help', $actual->getFirstArgument());
        $this->assertSame('list-help --help', (string) $actual);
        $_SERVER['argv'] = $saved;

        $command = new Command('list');

        $config->registerConfigCommandAlias($command);

        $this->assertSame(['list-help'], $command->getAliases());
    }

    public function testCustomCommands()
    {
        $configArray = [
            'commands' => [
                'customCommands' => [
                    GetCommand::class,
                    ['name' => GetCommand::class],
                ],
            ],
        ];

        $bufferedOutput = new BufferedOutput();
        $bufferedOutput->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        $config = new Config([], false, $bufferedOutput);
        $config->setConfig($configArray);

        /** @var Application|MockObject $application */
        $application = $this->createMock(Application::class);
        $application->expects(self::exactly(2))->method('add');

        $config->registerCustomCommands($application);
    }

    public function testRegisterCustomAutoloaders()
    {
        $array = ['autoloaders'      => ['$prefix' => '$path'], 'autoloaders_psr4' => ['$prefix\\' => '$path']];

        $expected =
            '<debug>Registered PSR-0 autoloader </debug> $prefix -> $path' . "\n" .
            '<debug>Registered PSR-4 autoloader </debug> $prefix\\ -> $path' . "\n";

        $bufferedOutput = new BufferedOutput();

        $config = new Config([], false, $bufferedOutput);
        $config->setConfig($array);

        $classLoader = new ClassLoader();
        $config->registerCustomAutoloaders($classLoader);

        $bufferedOutput->setVerbosity(BufferedOutput::VERBOSITY_DEBUG);
        $config->registerCustomAutoloaders($classLoader);

        $this->assertSame($expected, $bufferedOutput->fetch());
    }

    public function testLoadPartialConfig()
    {
        $config = new Config();
        $this->assertSame([], $config->getDetectSubFolders());
        $config->loadPartialConfig(false);
        $actual = $config->getDetectSubFolders();
        $this->assertIsArray($actual);
        $this->assertNotSame([], $actual);
    }
}
