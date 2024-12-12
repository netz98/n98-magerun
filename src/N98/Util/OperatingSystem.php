<?php

declare(strict_types=1);

namespace N98\Util;

/**
 * Class OperatingSystem
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class OperatingSystem
{
    public const OS_DARWIN  = 'darwin';

    public const OS_LINUX   = 'linux';

    public const OS_MAC     = 'mac';

    public const OS_NETWARE = 'netware';

    public const OS_WINDOWS = 'win';

    /**
     * @var int
     */
    public const UID_ROOT = 0;

    /**
     * Returns true if operating system is
     * based on GNU linux.
     */
    public static function isLinux(): bool
    {
        return (bool) stristr(PHP_OS, self::OS_LINUX);
    }

    /**
     * Returns true if operating system is
     * based on Microsoft Windows.
     */
    public static function isWindows(): bool
    {
        return strtolower(substr(PHP_OS, 0, 3)) === self::OS_WINDOWS;
    }

    /**
     * Returns true if operating system is
     * based on novell netware.
     */
    public static function isNetware(): bool
    {
        return (bool) stristr(PHP_OS, self::OS_NETWARE);
    }

    /**
     * Returns true if operating system is
     * based on Apple macOS.
     */
    public static function isMacOs(): bool
    {
        return stristr(PHP_OS, self::OS_DARWIN) || stristr(PHP_OS, self::OS_MAC);
    }

    public static function isProgramInstalled(string $program): bool
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
     * Home directory of the current user
     *
     * @return string|false false in case there is no environment variable related to the home directory
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
     */
    public static function isRoot(): bool
    {
        return function_exists('posix_getuid') && posix_getuid() === self::UID_ROOT;
    }

    /**
     * Get current working directory
     *
     * @return string|false the current working directory on success, or false on failure.
     */
    public static function getCwd()
    {
        return getcwd();
    }

    /**
     * Retrieve path to php binary
     */
    public static function getPhpBinary(): string
    {
        // PHP_BINARY (>= php 5.4)
        if (defined('PHP_BINARY')) {
            return PHP_BINARY;
        }

        if (self::isWindows()) {
            return 'php';
        }

        return '/usr/bin/env php';
    }

    public static function isBashCompatibleShell(): bool
    {
        $env = getenv('SHELL');
        return $env && in_array(basename($env), ['bash', 'zsh']);
    }
}
