<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class WindowsSystemTest
 *
 * @package N98\Util
 * @requires OS win
 */
final class WindowsSystemTest extends TestCase
{
    public function testIsProgramInstalled()
    {
        $this->assertTrue(WindowsSystem::isProgramInstalled('notepad'));

        $this->assertFalse(WindowsSystem::isProgramInstalled('notepad-that-never-made-it-into-windows-kernel'));

        $this->assertFalse(WindowsSystem::isProgramInstalled('invalid\\command*name|thisis'));
    }

    /**
     * @see isExecutableName
     * @return \Iterator<(int | string), mixed>
     */
    public function provideExecutableNames(): \Iterator
    {
        yield ['notepad', false];
        yield ['notepad.com', true];
        yield ['notepad.exe', true];
        yield ['notepad.exe.exe', true];
        yield ['notepad.eXe', true];
        yield ['notepad.EXE', true];
        yield ['notepad.bat', true];
        yield ['notepad.txt', false];
    }

    /**
     *
     * @param string $name
     * @param bool $expected
     * @dataProvider provideExecutableNames
     */
    public function testIsExecutableName($name, $expected)
    {
        $this->assertSame($expected, WindowsSystem::isExecutableName($name), $name);
    }
}
