<?php

namespace N98\Magento;

use N98\Util\ArrayFunctions;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Yaml\Yaml;
use org\bovigo\vfs\vfsStream;

class ApplicationTest extends TestCase
{
    public function testExecute()
    {
        /**
         * Check autoloading
         */

        /* @var $application Application */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $magentoRootFolder = getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');
        if (empty($magentoRootFolder)) {
            $this->markTestSkipped(
                'Please specify environment variable N98_MAGERUN_TEST_MAGENTO_ROOT with path to your test ' .
                'magento installation!'
            );
        }

        $application->setMagentoRootFolder($magentoRootFolder);

        $this->assertInstanceOf('\N98\Magento\Application', $application);
        $loader = $application->getAutoloader();
        $this->assertInstanceOf('\Composer\Autoload\ClassLoader', $loader);

        /**
         * Check version
         */
        $this->assertEquals($application::APP_VERSION, trim(file_get_contents(__DIR__ . '/../../../version.txt')));

        /* @var $loader \Composer\Autoload\ClassLoader */
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('N98', $prefixes);

        $distConfigArray = Yaml::parse(file_get_contents(__DIR__ . '/../../../config.yaml'));

        $configArray = array(
            'autoloaders' => array(
                'N98MagerunTest' => __DIR__ . '/_ApplicationTestSrc',
            ),
            'commands' => array(
                'customCommands' => array(
                    0 => 'N98MagerunTest\TestDummyCommand'
                ),
                'aliases' => array(
                    array(
                        'cl' => 'cache:list'
                    )
                ),
            ),
            'init' => array(
                'options' => array(
                    'config_model' => 'N98MagerunTest\AlternativeConfigModel',
                )
            )
        );

        $application->setAutoExit(false);
        $application->init(ArrayFunctions::mergeArrays($distConfigArray, $configArray));
        $application->run(new StringInput('list'), new NullOutput());

        // Check if autoloaders, commands and aliases are registered
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('N98MagerunTest', $prefixes);

        $testDummyCommand = $application->find('n98mageruntest:test:dummy');
        $this->assertInstanceOf('\N98MagerunTest\TestDummyCommand', $testDummyCommand);

        $commandTester = new CommandTester($testDummyCommand);
        $commandTester->execute(
            array(
                'command'    => $testDummyCommand->getName(),
            )
        );
        $this->assertContains('dummy', $commandTester->getDisplay());
        $this->assertTrue($application->getDefinition()->hasOption('root-dir'));

        // Test alternative config model
        $application->initMagento();
        if (version_compare(\Mage::getVersion(), '1.7.0.2', '>=')) {
            // config_model option is only available in Magento CE >1.6
            $this->assertInstanceOf('\N98MagerunTest\AlternativeConfigModel', \Mage::getConfig());
        }


        // check alias
        $this->assertInstanceOf('\N98\Magento\Command\Cache\ListCommand', $application->find('cl'));
    }

    public function testPlugins()
    {
        $this->getApplication(); // bootstrap implicit

        /**
         * Check autoloading
         */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $application->setMagentoRootFolder(getenv('N98_MAGERUN_TEST_MAGENTO_ROOT'));

        // Load plugin config
        $injectConfig = array(
            'plugin' => array(
                'folders' => array(
                    __DIR__ . '/_ApplicationTestModules'
                )
            )
        );
        $application->init($injectConfig);

        // Check for module command
        $this->assertInstanceOf('TestModule\FooCommand', $application->find('testmodule:foo'));
    }

    public function testComposer()
    {
        vfsStream::setup('root');
        vfsStream::create(
            array(
                'htdocs' => array(
                    'app' => array(
                        'Mage.php' => ''
                    )
                ),
                'vendor' => array(
                    'acme' => array(
                        'magerun-test-module' => array(
                            'n98-magerun.yaml' => file_get_contents(__DIR__ . '/_ApplicationTestComposer/n98-magerun.yaml'),
                            'src' => array(
                                'Acme' => array(
                                    'FooCommand.php' => file_get_contents(__DIR__ . '/_ApplicationTestComposer/FooCommand.php'),
                                )
                            )
                        )
                    ),
                    'n98' => array(
                        'magerun' => array(
                            'src' => array(
                                'N98' => array(
                                    'Magento' => array(
                                        'Command' => array(
                                            'ConfigurationLoader.php' => '',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );

        $configurationLoader = $this->getMock(
            '\N98\Magento\Command\ConfigurationLoader',
            array('getConfigurationLoaderDir'),
            array(array(), false, new NullOutput())
        );
        $configurationLoader
            ->expects($this->any())
            ->method('getConfigurationLoaderDir')
            ->will($this->returnValue(vfsStream::url('root/vendor/n98/magerun/src/N98/Magento/Command')));

        $application = require __DIR__ . '/../../../src/bootstrap.php';
        /* @var $application Application */
        $application->setMagentoRootFolder(vfsStream::url('root/htdocs'));
        $application->setConfigurationLoader($configurationLoader);
        $application->init();

        // Check for module command
        $this->assertInstanceOf('Acme\FooCommand', $application->find('acme:foo'));
    }
}
