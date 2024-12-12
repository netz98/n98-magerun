<?php

declare(strict_types=1);

/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class OperatingSystemTest
 *
 * @package N98\Util
 * @covers N98\Util\OperatingSystem
 */
final class OperatingSystemTest extends TestCase
{
    public function testOsDetection()
    {
        $matrix = [OperatingSystem::isLinux(), OperatingSystem::isWindows(), OperatingSystem::isMacOs(), OperatingSystem::isNetware()];

        $this->assertCount(4, $matrix, 'Number of OSes to check for');
        $this->assertCount(1, array_filter($matrix), 'One OS must be detected');
    }

    /**
     * @requires OS Linux
     */
    public function testIsLinux()
    {
        $this->assertTrue(OperatingSystem::isLinux());
        $this->assertFalse(OperatingSystem::isWindows());
        $this->assertFalse(OperatingSystem::isMacOs());
        $this->assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS ^Win
     */
    public function testIsWindows()
    {
        $this->assertTrue(OperatingSystem::isWindows());
        $this->assertFalse(OperatingSystem::isLinux());
        $this->assertFalse(OperatingSystem::isMacOs());
        $this->assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS Darwin|Mac
     */
    public function testIsMacOs()
    {
        $this->assertTrue(OperatingSystem::isMacOs());
        $this->assertFalse(OperatingSystem::isLinux());
        $this->assertFalse(OperatingSystem::isWindows());
        $this->assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS netware
     */
    public function testIsNetware()
    {
        $this->assertTrue(OperatingSystem::isNetware());
        $this->assertFalse(OperatingSystem::isLinux());
        $this->assertFalse(OperatingSystem::isWindows());
        $this->assertFalse(OperatingSystem::isMacOs());
    }

    public function testGetCwd()
    {
        $expected = getcwd();
        $this->assertEquals($expected, OperatingSystem::getCwd());
    }

    /**
     * @requires PHP 5.4
     */
    public function testPhpBinary()
    {
        $this->assertSame(PHP_BINARY, OperatingSystem::getPhpBinary());
    }
}
