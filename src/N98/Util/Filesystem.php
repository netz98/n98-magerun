<?php

namespace N98\Util;

class Filesystem
{
    /**
     * @param string $src
     * @param string $dst
     */
    public function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                }
                else {
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
        foreach(glob($dir . '/*') as $file) {
            if (is_dir($file))
                $this->recursiveRemoveDirectory($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }
}