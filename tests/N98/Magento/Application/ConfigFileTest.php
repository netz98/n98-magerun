<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use InvalidArgumentException;
use MongoDB\Driver\Exception\RuntimeException;
use N98\Magento\Command\TestCase;

/**
 * Class ConfigFileTest
 *
 * @covers  N98\Magento\Application\ConfigFile
 * @package N98\Magento\Application
 */
class ConfigFileTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $configFile = new ConfigFile();
        $this->assertInstanceOf('\N98\Magento\Application\ConfigFile', $configFile);

        $configFile = ConfigFile::createFromFile(__FILE__);
        $this->assertInstanceOf('\N98\Magento\Application\ConfigFile', $configFile);
    }

    /**
     * @test
     */
    public function applyVariables()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- %root%');
        $configFile->applyVariables("root-folder");

        $this->assertSame(array('root-folder'), $configFile->toArray());
    }

    /**
     * @test
     */
    public function mergeArray()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- bar');
        $result = $configFile->mergeArray(array('foo'));

        $this->assertSame(array('foo', 'bar'), $result);
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to parse config-file 'data://,'
     */
    public function parseEmptyFile()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,');
        $this->addToAssertionCount(1);
        $configFile->toArray();
        $this->fail('An expected exception has not been thrown.');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function invalidFileThrowsException()
    {
        @ConfigFile::createFromFile(":");
    }
}
