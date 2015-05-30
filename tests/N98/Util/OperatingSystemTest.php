<?php

namespace N98\Util;


class OperatingSystemTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @requires OS Linux
     */
    public function testIsLinux() {
        $this->assertTrue(OperatingSystem::isLinux());
    }

    /**
     * @requires OS Win
     */
    public function testIsWindows(){
        $this->assertTrue(OperatingSystem::isWindows());
    }

    /**
     * @requires OS Darwin|Mac
     */
    public function testIsMacOs() {
        $this->assertTrue(OperatingSystem::isMacOs());
    }

    /**
     * @requires OS netware
     */
    public function testIsNetware() {
        $this->assertTrue(OperatingSystem::isNetware());
    }
}
