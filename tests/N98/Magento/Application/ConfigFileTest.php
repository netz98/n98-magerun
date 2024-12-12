<?php

declare(strict_types=1);

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
final class ConfigFileTest extends TestCase
{
    public function testCreation()
    {
        $configFile = new ConfigFile();
        $this->assertInstanceOf(ConfigFile::class, $configFile);

        $configFile = ConfigFile::createFromFile(__FILE__);
        $this->assertInstanceOf(ConfigFile::class, $configFile);
    }

    public function testApplyVariables()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- %root%');
        $configFile->applyVariables('root-folder');

        $this->assertSame(['root-folder'], $configFile->toArray());
    }

    public function testMergeArray()
    {
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,- bar');

        $result = $configFile->mergeArray(['foo']);

        $this->assertSame(['foo', 'bar'], $result);
    }

    public function testParseEmptyFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to parse config-file 'data://,'");
        $configFile = new ConfigFile();
        $configFile->loadFile('data://,');
        $this->addToAssertionCount(1);
        $configFile->toArray();
        self::fail('An expected exception has not been thrown.');
    }

    public function testInvalidFileThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        @ConfigFile::createFromFile(':');
    }
}
