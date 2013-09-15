<?php

namespace N98\Util;

class OperatingSystem
{
    /**
     * Returns true if operating system is
     * based on GNU linux.
     *
     * @return boolean
     */
    public static function isLinux()
    {
        return stristr(PHP_OS, 'linux');
    }

    /**
     * Returns true if operating system is
     * based on Microsoft Windows.
     *
     * @return boolean
     */
    public static function isWindows()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }

    /**
     * Returns true if operating system is
     * based on novell netware.
     *
     * @return boolean
     */
    public static function isNetware()
    {
        return stristr(PHP_OS, 'netware');
    }

    /**
     * Returns true if operating system is
     * based on apple MacOS.
     *
     * @return boolean
     */
    public static function isMacOs()
    {
        return stristr(PHP_OS, 'darwin') || stristr(PHP_OS, 'mac');
    }

    /**
     * @param string $program
     * @return bool
     */
    public static function isProgramInstalled($program)
    {
        $out = null;
        $return = null;
        @exec('which ' . $program, $out, $return);

        return $return === 0;
    }
}