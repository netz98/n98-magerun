<?php

declare(strict_types=1);

namespace N98\Magento;

use Composer\Autoload\ClassLoader;
use Mage;
use N98\Magento\Command\Cache\ListCommand;
use N98\Magento\Application\ConfigurationLoader;
use PHPUnit\Framework\MockObject\MockObject;
use N98\Magento\Command\TestCase;
use N98\Util\ArrayFunctions;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

final class ApplicationTest extends TestCase
{
    public function testExecute()
    {
        /**
         * Check autoloading
         */

        /* @var $application Application */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $application->setMagentoRootFolder($this->getTestMagentoRoot());

        $this->assertInstanceOf(Application::class, $application);
        $loader = $application->getAutoloader();
        $this->assertInstanceOf(ClassLoader::class, $loader);

        /**
         * Check version
         */
        $this->assertSame(Application::APP_VERSION, trim(file_get_contents(__DIR__ . '/../../../version.txt')));

        /* @var $loader \Composer\Autoload\ClassLoader */
        $prefixes = $loader->getPrefixesPsr4();
        $this->assertArrayHasKey('N98\\', $prefixes);

        $distConfigArray = Yaml::parse(file_get_contents(__DIR__ . '/../../../config.yaml'));

        $configArray = ['autoloaders' => ['N98MagerunTest' => __DIR__ . '/_ApplicationTestSrc'], 'commands' => ['customCommands' => [0 => \N98MagerunTest\TestDummyCommand::class], 'aliases' => [['cl' => 'cache:list']]], 'init' => ['options' => ['config_model' => \N98MagerunTest\AlternativeConfigModel::class]]];

        $application->setAutoExit(false);
        $application->init(ArrayFunctions::mergeArrays($distConfigArray, $configArray));
        $application->run(new StringInput('list'), new NullOutput());

        // Check if autoloaders, commands and aliases are registered
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('N98MagerunTest', $prefixes);

        $testDummyCommand = $application->find('n98mageruntest:test:dummy');
        $this->assertInstanceOf(\N98MagerunTest\TestDummyCommand::class, $testDummyCommand);

        $commandTester = new CommandTester($testDummyCommand);
        $commandTester->execute(
            ['command'    => $testDummyCommand->getName()],
        );
        $this->assertStringContainsString('dummy', $commandTester->getDisplay());
        $this->assertTrue($application->getDefinition()->hasOption('root-dir'));

        // Test alternative config model
        $application->initMagento();
        if (version_compare(Mage::getVersion(), '1.7.0.2', '>=')) {
            // config_model option is only available in Magento CE >1.6
            $this->assertInstanceOf(\N98MagerunTest\AlternativeConfigModel::class, Mage::getConfig());
        }

        // check alias
        $this->assertInstanceOf(ListCommand::class, $application->find('cl'));
    }

    public function testPlugins()
    {
        $this->getApplication(); // bootstrap implicit

        /**
         * Check autoloading
         */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $application->setMagentoRootFolder($this->getTestMagentoRoot());

        // Load plugin config
        $injectConfig = ['plugin' => ['folders' => [__DIR__ . '/_ApplicationTestModules']]];
        $application->init($injectConfig);

        // Check for module command
        $this->assertInstanceOf(\TestModule\FooCommand::class, $application->find('testmodule:foo'));
    }

    public function testComposer()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['htdocs' => ['app' => ['Mage.php' => '']], 'vendor' => ['acme' => ['magerun-test-module' => ['n98-magerun.yaml' => file_get_contents(__DIR__ . '/_ApplicationTestComposer/n98-magerun.yaml'), 'src'              => ['Acme' => ['FooCommand.php' => file_get_contents(__DIR__ . '/_ApplicationTestComposer/FooCommand.php')]]]], 'n98' => ['magerun' => ['src' => ['N98' => ['Magento' => ['Command' => ['ConfigurationLoader.php' => '']]]]]]]],
        );

        /** @var ConfigurationLoader|MockObject $configurationLoader */
        $configurationLoader = $this->getMockBuilder(ConfigurationLoader::class)
            ->setMethods(['getConfigurationLoaderDir'])
            ->setConstructorArgs([[], false, new NullOutput()])
            ->getMock();

        $configurationLoader
            ->method('getConfigurationLoaderDir')
            ->willReturn(vfsStream::url('root/vendor/n98/magerun/src/N98/Magento/Command'));

        /* @var $application Application */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $application->setMagentoRootFolder(vfsStream::url('root/htdocs'));
        $application->setConfigurationLoader($configurationLoader);
        $application->init();

        // Check for module command
        $this->assertInstanceOf(\Acme\FooCommand::class, $application->find('acme:foo'));
    }
}
