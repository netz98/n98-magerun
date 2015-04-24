<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util;

/**
 * Class WindowsSystem
 *
 * Utility class with global static functions.
 *
 * @package N98\Util
 */
class WindowsSystem
{
    const PATH_SEPARATOR = ';';

    const FORBIDDEN_CHARS = '<>:"/\|?*';

    /**
     * @var WindowsSystem
     */
    private static $instance;

    /**
     * @var array
     */
    private $exts;

    /**
     * an instance is bootstrapped in to prevent initialization overhead
     *
     * @return WindowsSystem
     */
    private static function getInstance()
    {
        self::$instance || self::$instance = new WindowsSystem();

        return self::$instance;
    }

    private function  __construct()
    {
    }

    /**
     * @return array keys are uppercase extensions incl. dot
     */
    private function getExecuteableExtesions()
    {
        // PATHEXT=.COM;.EXE;.BAT;.CMD;.VBS;.VBE;.JS;.JSE;.WSF;.WSH;.PSC1
        $this->exts || $this->exts = array_flip(array_map('strtoupper', explode(self::PATH_SEPARATOR, getenv('PATHEXT'))));

        return $this->exts;
    }

    /**
     * a name is executable based on it's extension
     *
     * @param $name
     * @return bool
     */
    public static function isExecutableName($name)
    {
        // invalid name is never executable
        if (false !== strpbrk($name, self::FORBIDDEN_CHARS)) {
            return false;
        }

        $compare = '.' . strtoupper(pathinfo($name, PATHINFO_EXTENSION));

        if ($compare === '.') {
            return false;
        }

        $exts = self::getInstance()->getExecuteableExtesions();

        return isset($exts[$compare]);
    }

    /**
     * a program (by it's basename) is available on system for execution
     *
     * @param $program
     * @return bool
     */
    public static function isProgramInstalled($program)
    {
        // programs with an invalid name do not exist
        if (false !== strpbrk($program, self::FORBIDDEN_CHARS)) {
            return false;
        }

        $isExecutable = self::isExecutableName($program);


        $paths = explode(self::PATH_SEPARATOR, getenv('PATH'));
        array_unshift($paths, getcwd());
        $exts = self::getInstance()->getExecuteableExtesions();


        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $file = $path . '/' . $program;

            if ($isExecutable && is_readable($file)) {
                return true;
            }

            foreach ($exts as $ext => $index) {
                $fileEx = $file . $ext;
                if (is_readable($fileEx)) {
                    return true;
                }
            }
        }

        return false;
    }
}
