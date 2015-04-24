<?php

namespace N98\Util;

use Psy\Exception\RuntimeException;

class Filesystem
{
    /**
     * @param string $src
     * @param string $dst
     * @param array  $blacklist
     *
     * @return void
     */
    public function recursiveCopy($src, $dst, $blacklist = array())
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            @mkdir($dst);
        }

        if (!is_dir($dst)) {
            throw new RuntimeException(sprintf('Destination directory <%s> error', $dst));
        }

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..') && !in_array($file, $blacklist)) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file, $blacklist);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * @param string $directory
     * @param bool empty
     * @see http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
     *
     * @return bool
     */
    public function recursiveRemoveDirectory($directory, $empty = false)
    {
        // if the path has a slash at the end we remove it here
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        // if the path is not valid or is not a directory ...
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;

            // ... if the path is not readable
        } elseif (!is_readable($directory)) {
            return false;
        } else {

            // we open the directory
            $handle = opendir($directory);

            // and scan through the items inside
            while (false !== ($item = readdir($handle))) {
                // if the filepointer is not the current directory
                // or the parent directory
                if ($item != '.' && $item != '..') {
                    // we build the new path to delete
                    $path = $directory . '/' . $item;

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
            }
            // close the directory
            closedir($handle);

            // if the option to empty is not set to true
            if ($empty == false) {
                // try to delete the now empty directory
                return rmdir($directory);
            }
            // return success
            return true;
        }
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
        $units = array('B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f%s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
