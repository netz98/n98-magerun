<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

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
        self::assertInstanceOf('\N98\Magento\Application\ConfigFile', $configFile);

        $configFile = ConfigFile::createFromFile(__FILE__);
        self::assertInstanceOf('\N98\Magento\Application\ConfigFile', $configFile);
    }

    /**
     * @test
     */
    public function applyVariables()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- %root%');
        $configFile->applyVariables("root-folder");

        self::assertSame(array('root-folder'), $configFile->toArray());
    }

    /**
     * @test
     */
    public function mergeArray()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- bar');
        $result = $configFile->mergeArray(array('foo'));

        self::assertSame(array('foo', 'bar'), $result);
    }

    /**
     * @test
     */
    public function parseEmptyFile()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse config-file \'data://,\'');
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,');
        $this->addToAssertionCount(1);
        $configFile->toArray();
        self::fail('An expected exception has not been thrown.');
    }

    /**
     * @test
     */
    public function invalidFileThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        @ConfigFile::createFromFile(":");
    }
}
