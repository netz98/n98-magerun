<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util;

/**
 * Class WindowsSystemTest
 *
 * @package N98\Util
 * @requires OS win
 */
class WindowsSystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function isProgramInstalled()
    {

        $this->assertTrue(WindowsSystem::isProgramInstalled("notepad"));

        $this->assertFalse(WindowsSystem::isProgramInstalled("notepad-that-never-made-it-into-windows-kernel"));

        $this->assertFalse(WindowsSystem::isProgramInstalled("invalid\\command*name|thisis"));
    }

    /**
     * @see isExecutableName
     * @return array
     */
    public function provideExecutableNames()
    {
        return array(
            array("notepad", false),
            array("notepad.com", true),
            array("notepad.exe", true),
            array("notepad.exe.exe", true),
            array("notepad.eXe", true),
            array("notepad.EXE", true),
            array("notepad.bat", true),
            array("notepad.txt", false),
        );
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
        $this->assertSame($expected, WindowsSystem::isExecutableName($name), $name);
    }
}
