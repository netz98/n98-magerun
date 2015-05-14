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
        return stristr(self::_getOs(), 'linux');
    }

    /**
     * Returns true if operating system is
     * based on Microsoft Windows.
     *
     * @return boolean
     */
    public static function isWindows()
    {
        return strtolower(substr(self::_getOs(), 0, 3)) === 'win';
    }

    /**
     * Returns true if operating system is
     * based on novell netware.
     *
     * @return boolean
     */
    public static function isNetware()
    {
        return stristr(self::_getOs(), 'netware');
    }

    /**
     * Returns true if operating system is
     * based on apple MacOS.
     *
     * @return boolean
     */
    public static function isMacOs()
    {
        return stristr(self::_getOs(), 'darwin') || stristr(self::_getOs(), 'mac');
    }

    /**
     * @param string $program
     * @return bool
     */
    public static function isProgramInstalled($program)
    {
        if (self::isWindows()) {
            return WindowsSystem::isProgramInstalled($program);
        }

        $out = null;
        $return = null;
        @exec('which ' . $program, $out, $return);

        return $return === 0;
    }

    /**
     * @return string
     */
    protected static function _getOs()
    {
        return PHP_OS;
    }

    /**
     * @return string
     */
    public static function getHomeDir()
    {
        if (self::isWindows()) {
            return getenv('USERPROFILE');
        } else {
            return getenv('HOME');
        }
    }
}
