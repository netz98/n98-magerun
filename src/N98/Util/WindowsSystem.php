<?php

declare(strict_types=1);

namespace N98\Util;

/**
 * Class WindowsSystem
 *
 * Utility class with global static functions.
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
final class WindowsSystem
{
    public const PATH_SEPARATOR = ';';

    public const FORBIDDEN_CHARS = '<>:"/\|?*';

    private static WindowsSystem $windowsSystem;

    private array $extensions;

    /**
     * an instance is bootstrapped in to prevent initialization overhead
     */
    private static function getInstance(): WindowsSystem
    {
        self::$windowsSystem || (self::$windowsSystem = new WindowsSystem()) instanceof \N98\Util\WindowsSystem;
        return self::$windowsSystem;
    }

    private function __construct()
    {
    }

    /**
     * @return array keys are uppercase extensions incl. dot
     */
    private function getExecutableExtensions(): array
    {
        // PATHEXT=.COM;.EXE;.BAT;.CMD;.VBS;.VBE;.JS;.JSE;.WSF;.WSH;.PSC1
        $this->extensions || $this->extensions = array_flip(
            array_map('strtoupper', explode(self::PATH_SEPARATOR, getenv('PATHEXT')))
        );

        return $this->extensions;
    }

    /**
     * A name is executable based on it's extension
     */
    public static function isExecutableName(string $name): bool
    {
        // invalid name is never executable
        if (false !== strpbrk($name, self::FORBIDDEN_CHARS)) {
            return false;
        }

        $compare = '.' . strtoupper(pathinfo($name, PATHINFO_EXTENSION));
        if ($compare === '.') {
            return false;
        }

        $extensions = self::getInstance()->getExecutableExtensions();
        return isset($extensions[$compare]);
    }

    /**
     * a program (by its basename) is available on system for execution
     */
    public static function isProgramInstalled(string $program): bool
    {
        // programs with an invalid name do not exist
        if (false !== strpbrk($program, self::FORBIDDEN_CHARS)) {
            return false;
        }

        $isExecutable = self::isExecutableName($program);

        $paths = explode(self::PATH_SEPARATOR, getenv('PATH'));
        array_unshift($paths, getcwd());
        $extensions = self::getInstance()->getExecutableExtensions();

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $file = $path . '/' . $program;

            if ($isExecutable && is_readable($file)) {
                return true;
            }

            foreach (array_keys($extensions) as $ext) {
                $fileEx = $file . $ext;
                if (is_readable($fileEx)) {
                    return true;
                }
            }
        }

        return false;
    }
}
