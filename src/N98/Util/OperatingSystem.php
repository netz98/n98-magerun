<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

/**
 * Class OperatingSystem
 *
 * @package N98\Util
 */
class OperatingSystem
{
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
        return (bool) stristr(PHP_OS, 'linux');
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
        return (bool) stristr(PHP_OS, 'netware');
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
    public static function getHomeDir()
    {
        if (self::isWindows()) {
            return getenv('USERPROFILE');
        }

        return getenv('HOME');
    }

    /**
     * Test for Root UID on a POSIX system if posix_getuid() is available.
     *
     * Returns false negatives if posix_getuid() is not available.
     *
     * @return bool
     */
    public static function isRoot()
    {
        return function_exists('posix_getuid') && posix_getuid() === self::UID_ROOT;
    }

    /**
     * get current working directory
     *
     * @return string the current working directory on success, or false on failure.
     */
    public static function getCwd()
    {
        return getcwd();
    }
}
