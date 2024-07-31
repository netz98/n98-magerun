<?php
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
class WindowsSystemTest extends TestCase
{
    /**
     * @test
     */
    public function isProgramInstalled()
    {
        self::assertTrue(WindowsSystem::isProgramInstalled('notepad'));

        self::assertFalse(WindowsSystem::isProgramInstalled('notepad-that-never-made-it-into-windows-kernel'));

        self::assertFalse(WindowsSystem::isProgramInstalled('invalid\\command*name|thisis'));
    }

    /**
     * @see isExecutableName
     * @return array
     */
    public function provideExecutableNames()
    {
        return [['notepad', false], ['notepad.com', true], ['notepad.exe', true], ['notepad.exe.exe', true], ['notepad.eXe', true], ['notepad.EXE', true], ['notepad.bat', true], ['notepad.txt', false]];
    }

    /**
     * @test
     *
     * @param string $name
     * @param bool $expected
     * @dataProvider provideExecutableNames
     */
    public function isExecutableName($name, $expected)
    {
        self::assertSame($expected, WindowsSystem::isExecutableName($name), $name);
    }
}
