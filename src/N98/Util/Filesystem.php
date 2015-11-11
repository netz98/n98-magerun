<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use RuntimeException;

/**
 * Class Filesystem
 *
 * @package N98\Util
 */
class Filesystem
{
    /**
     * @param string $src
     * @param string $dst
     * @param string[]  $blacklist
     *
     * @return void
     */
    public function recursiveCopy($src, $dst, $blacklist = array())
    {
        if (!is_dir($dst)) {
            @mkdir($dst, 0777, true);
        }

        if (!is_dir($dst)) {
            throw new RuntimeException(sprintf('Destination directory <%s> error', $dst));
        }

        $handle = opendir($src);

        if (!$handle) {
            throw new RuntimeException(sprintf('Source directory <%s> error', $src));
        }

        $skip = array_merge(array(".", ".."), $blacklist);
        $stack = array();

        while (false !== ($file = readdir($handle))) {
            if (in_array($file, $skip)) {
                continue;
            }

            if (is_dir($src . '/' . $file)) {
                $stack[] = $file;
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($handle);

        foreach ($stack as $file) {
            $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file, $blacklist);
        }
    }

    /**
     * @param string $directory
     * @param bool $empty
     *
     * @see http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
     *
     * @return bool
     */
    public function recursiveRemoveDirectory($directory, $empty = false)
    {
        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) === '/') {
            $directory = substr($directory, 0, -1);
        }

        // if the path is not valid or is not a directory ...
        // ... if the path is not readable
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }

        // we open the directory
        $handle = opendir($directory);

        if (!$handle) {
            throw new RuntimeException(sprintf('Directory <%s> error', $directory));
        }

        $skip = array(".", "..");

        // and scan through the items inside
        while (false !== ($file = readdir($handle))) {
            // if the filepointer is not the current directory
            // or the parent directory
            if (in_array($file, $skip)) {
                continue;
            }

            // we build the new path to delete
            $path = $directory . '/' . $file;

            // if the new path is a directory
            // don't recursively delete symlinks - just remove the actual link
            // this is helpful for extensions sym-linked from vendor directory
            // previous behaviour would wipe out the files in the vendor directory
            if (!is_link($path) && is_dir($path)) {
                // we call this function with the new path
                $this->recursiveRemoveDirectory($path);

                // if the new path is a file
            } else {
                // we remove the file
                unlink($path);
            }
        }
        closedir($handle);

        // if the option not empty
        if (!$empty) {
            return rmdir($directory);
        }

        // return success
        return true;
    }

    /**
     * @param int $bytes
     * @param int $decimals
     *
     * @see http://www.php.net/manual/en/function.filesize.php#106569
     *
     * @return string
     */
    public static function humanFileSize($bytes, $decimals = 2)
    {
        $units  = array('B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f%s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
