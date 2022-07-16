<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Application;

use RuntimeException;
use InvalidArgumentException;
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
        self::assertInstanceOf(ConfigFile::class, $configFile);

        $configFile = ConfigFile::createFromFile(__FILE__);
        self::assertInstanceOf(ConfigFile::class, $configFile);
    }

    /**
     * @test
     */
    public function applyVariables()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- %root%');
        $configFile->applyVariables("root-folder");

        self::assertSame(['root-folder'], $configFile->toArray());
    }

    /**
     * @test
     */
    public function mergeArray()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- bar');
        $result = $configFile->mergeArray(['foo']);

        self::assertSame(['foo', 'bar'], $result);
    }

    /**
     * @test
     */
    public function parseEmptyFile()
    {
        $this->expectException(RuntimeException::class);
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
        $this->expectException(InvalidArgumentException::class);
        @ConfigFile::createFromFile(":");
    }
}
