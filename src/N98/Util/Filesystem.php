<?php

namespace N98\Util;

class Filesystem
{
    /**
     * @param string $src
     * @param string $dst
     * @param array $blacklist
     */
    public function recursiveCopy($src, $dst, $blacklist = array())
    {
        $dir = opendir($src);
        @mkdir($dst);
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
     * @param $dir
     */
    public function recursiveRemoveDirectory($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}