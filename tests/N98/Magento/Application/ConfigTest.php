<?php
/*
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use Symfony\Component\Console\Input\InputInterface;
use N98\Magento\Command\Config\GetCommand;
use N98\Magento\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Composer\Autoload\ClassLoader;
use ErrorException;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class ConfigTest
 *
 * @covers  N98\Magento\Application\Config
 * @package N98\Magento\Application
 */
class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $config = new Config();
        self::assertInstanceOf(__NAMESPACE__ . '\\Config', $config);
    }

    /**
     * @test
     */
    public function loader()
    {
        $config = new Config();

        try {
            $config->load();
            self::fail('An expected exception was not thrown');
        } catch (ErrorException $e) {
            self::assertEquals('Configuration not yet fully loaded', $e->getMessage());
        }

        self::assertEquals([], $config->getConfig());

        $loader = $config->getLoader();
        self::assertInstanceOf(__NAMESPACE__ . '\\ConfigurationLoader', $loader);
        self::assertSame($loader, $config->getLoader());

        $loader->loadStageTwo("");
        $config->load();

        self::assertIsArray($config->getConfig());
        self::assertGreaterThan(4, count($config->getConfig()));

        $config->setLoader($loader);
    }

    /**
     * config array setter is used in some tests on @see \N98\Magento\Application::setConfig()
     *
     * @test
     */
    public function setConfig()
    {
        $config = new Config();
        $config->setConfig([0, 1, 2]);
        $actual = $config->getConfig();
        self::assertSame($actual[1], 1);
    }

    /**
     * @test
     */
    public function configCommandAlias()
    {
        $config = new Config();
        $input = new ArgvInput();
        $actual = $config->checkConfigCommandAlias($input);
        self::assertInstanceOf(InputInterface::class, $actual);

        $saved = $_SERVER['argv'];
        {
            $config->setConfig(['commands' => ['aliases' => [['list-help' => 'list --help']]]]);
            $definition = new InputDefinition();
            $definition->addArgument(new InputArgument('command'));

            $argv = ['/path/to/command', 'list-help'];
            $_SERVER['argv'] = $argv;
            $input = new ArgvInput($argv, $definition);
            self::assertSame('list-help', (string) $input);
            $actual = $config->checkConfigCommandAlias($input);
            self::assertSame('list-help', $actual->getFirstArgument());
            self::assertSame('list-help --help', (string) $actual);
        }
        $_SERVER['argv'] = $saved;

        $command = new Command('list');

        $config->registerConfigCommandAlias($command);

        self::assertSame(['list-help'], $command->getAliases());
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function customCommands()
    {
        $configArray = [
            'commands' => [
                'customCommands' => [
                    GetCommand::class,
                    ['name' => GetCommand::class],
                ],
            ],
        ];

        $output = new BufferedOutput();
        $output->setVerbosity($output::VERBOSITY_DEBUG);

        $config = new Config([], false, $output);
        $config->setConfig($configArray);

        /** @var Application|MockObject $application */
        $application = $this->createMock(Application::class);
        $application->expects(self::exactly(2))->method('add');

        $config->registerCustomCommands($application);
    }

    /**
     * @test
     */
    public function registerCustomAutoloaders()
    {
        $array = ['autoloaders'      => ['$prefix' => '$path'], 'autoloaders_psr4' => ['$prefix\\' => '$path']];

        $expected =
            '<debug>Registered PSR-0 autoloader </debug> $prefix -> $path' . "\n" .
            '<debug>Registered PSR-4 autoloader </debug> $prefix\\ -> $path' . "\n";

        $output = new BufferedOutput();

        $config = new Config([], false, $output);
        $config->setConfig($array);

        $autloader = new ClassLoader();
        $config->registerCustomAutoloaders($autloader);

        $output->setVerbosity($output::VERBOSITY_DEBUG);
        $config->registerCustomAutoloaders($autloader);

        self::assertSame($expected, $output->fetch());
    }

    /**
     * @test
     */
    public function loadPartialConfig()
    {
        $config = new Config();
        self::assertEquals([], $config->getDetectSubFolders());
        $config->loadPartialConfig(false);
        $actual = $config->getDetectSubFolders();
        self::assertIsArray($actual);
        self::assertNotEquals([], $actual);
    }
}
