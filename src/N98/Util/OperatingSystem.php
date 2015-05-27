<?php

namespace N98\Util;

class OperatingSystem
{


    /**
     * @var string
     */
    public static $magerunFolder = '/.n98-magerun/';

    /**
     * @var int
     */
    const UID_ROOT = 0;

    /**
     * Returns true if operating system is
     * based on GNU linux.
     *
     * @return boolean
     */
    public static function isLinux()
    {
        return (bool)stristr(self::_getOs(), 'linux');
    }

    /**
     * Returns true if operating system is
     * based on Microsoft Windows.
     *
     * @return boolean
     */
    public static function isWindows()
    {
        return (bool)strtolower(substr(self::_getOs(), 0, 3)) === 'win';
    }

    /**
     * Returns true if operating system is
     * based on novell netware.
     *
     * @return boolean
     */
    public static function isNetware()
    {
        return (bool)stristr(self::_getOs(), 'netware');
    }

    /**
     * Returns true if operating system is
     * based on apple MacOS.
     *
     * @return boolean
     */
    public static function isMacOs()
    {
        return (bool)stristr(self::_getOs(), 'darwin') || stristr(self::_getOs(), 'mac');
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
        }
        return getenv('HOME');
    }


    /**
     * @return bool
     */
    public static function hasShell()
    {
        return OperatingSystem::isLinux() || OperatingSystem::isMacOs();
    }

    /**
     * @return bool
     */
    public static function isRoot()
    {
        return self::hasShell() && function_exists('posix_getuid') && posix_getuid() === self::UID_ROOT;
    }
}

