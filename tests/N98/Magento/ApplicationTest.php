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
                        'cf' => 'cache:flush'
                    )
                ),
            ),
        );
        $application->setConfig($configArray);
        $application->setAutoExit(false);
        $application->run(new StringInput('list'), new NullOutput());

        // Check if autoloaders, commands and aliases are registered
        $prefixes = $loader->getPrefixes();
        $this->assertArrayHasKey('N98MagerunTest', $prefixes);

        $testDummyCommand = $application->find('n98mageruntest:test:dummy');
        $this->assertInstanceOf('\N98MagerunTest\TestDummyCommand', $testDummyCommand);

        // check alias
        $this->assertInstanceOf('\N98\Magento\Command\Cache\FlushCommand', $application->find('cf'));
    }
}