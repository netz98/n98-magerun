<?php

namespace N98\Magento;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;
use N98\Magento\Application;

class ApplicationTest extends TestCase
{
    public function testExecute()
    {
        /**
         * Check autoloading
         */
        $application = require __DIR__ . '/../../../src/bootstrap.php';
        $application->setMagentoRootFolder(getenv('N98_MAGERUN_TEST_MAGENTO_ROOT'));

        /* @var $application Application */
        $this->assertInstanceOf('\N98\Magento\Application', $application);
        $loader = $application->getAutoloader();
        $this->assertInstanceOf('\Composer\Autoload\ClassLoader', $loader);

        /* @var $loader \Composer\Autoload\ClassLoader */
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('N98', $prefixes);

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
        );

        $application->setAutoExit(false);
        $application->init($configArray);
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

        // check alias
        $this->assertInstanceOf('\N98\Magento\Command\Cache\ListCommand', $application->find('cl'));
    }

    public function testPlugins()
    {
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
}