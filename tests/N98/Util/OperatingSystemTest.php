<?php
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
class OperatingSystemTest extends TestCase
{
    /**
     * @test
     */
    public function osDetection()
    {
        $matrix = [OperatingSystem::isLinux(), OperatingSystem::isWindows(), OperatingSystem::isMacOs(), OperatingSystem::isNetware()];

        self::assertCount(4, $matrix, 'Number of OSes to check for');
        self::assertCount(1, array_filter($matrix), 'One OS must be detected');
    }

    /**
     * @requires OS Linux
     */
    public function testIsLinux()
    {
        self::assertTrue(OperatingSystem::isLinux());
        self::assertFalse(OperatingSystem::isWindows());
        self::assertFalse(OperatingSystem::isMacOs());
        self::assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS ^Win
     */
    public function testIsWindows()
    {
        self::assertTrue(OperatingSystem::isWindows());
        self::assertFalse(OperatingSystem::isLinux());
        self::assertFalse(OperatingSystem::isMacOs());
        self::assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS Darwin|Mac
     */
    public function testIsMacOs()
    {
        self::assertTrue(OperatingSystem::isMacOs());
        self::assertFalse(OperatingSystem::isLinux());
        self::assertFalse(OperatingSystem::isWindows());
        self::assertFalse(OperatingSystem::isNetware());
    }

    /**
     * @requires OS netware
     */
    public function testIsNetware()
    {
        self::assertTrue(OperatingSystem::isNetware());
        self::assertFalse(OperatingSystem::isLinux());
        self::assertFalse(OperatingSystem::isWindows());
        self::assertFalse(OperatingSystem::isMacOs());
    }

    /**
     * @test
     */
    public function getCwd()
    {
        $expected = getcwd();
        self::assertEquals($expected, OperatingSystem::getCwd());
    }

    /**
     * @test
     * @requires PHP 5.4
     */
    public function phpBinary()
    {
        self::assertEquals(PHP_BINARY, OperatingSystem::getPhpBinary());
    }
}
