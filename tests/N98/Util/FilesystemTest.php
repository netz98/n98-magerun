<?php

namespace N98\Util;

use N98\Util\Filesystem;

/**
 * Class FilesystemTest
 * @package N98\Util
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    protected $fileSystem;

    public function setUp()
    {
        $this->fileSystem = new Filesystem();
    }

    public function testRecursiveCopy()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        $folder1    = $basePath . "/folder1";
        $folder2    = $basePath . "/folder2";
        $file1      = $folder1 . "/file1.txt";
        $file2      = $folder2 . "/file2.txt";
        $dest       = sys_get_temp_dir() . "/n98_copy_dest";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveCopy($basePath, $dest);
        $this->assertFileExists($dest . "/folder1/file1.txt");
        $this->assertFileExists($dest . "/folder2/file2.txt");

        //cleanup
        unlink($file1);
        unlink($file2);
        rmdir($folder1);
        rmdir($folder2);
        rmdir($basePath);

        unlink($dest . "/folder1/file1.txt");
        unlink($dest . "/folder2/file2.txt");
        rmdir($dest . "/folder1");
        rmdir($dest . "/folder2");
        rmdir($dest);
    }

    public function testRecursiveCopyWithBlacklist()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        $folder1    = $basePath . "/folder1";
        $folder2    = $basePath . "/folder2";
        $file1      = $folder1 . "/file1.txt";
        $ignoreMe   = $folder1 . "/ignore.me";
        $file2      = $folder2 . "/file2.txt";
        $dest       = sys_get_temp_dir() . "/n98_copy_dest";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveCopy($basePath, $dest, array('ignore.me'));
        $this->assertFileExists($dest . "/folder1/file1.txt");
        $this->assertFileExists($dest . "/folder2/file2.txt");
        $this->assertFileNotExists($dest . "/folder1/ignore.me");

        //cleanup
        unlink($file1);
        unlink($file2);
        rmdir($folder1);
        rmdir($folder2);
        rmdir($basePath);

        unlink($dest . "/folder1/file1.txt");
        unlink($dest . "/folder2/file2.txt");
        rmdir($dest . "/folder1");
        rmdir($dest . "/folder2");
        rmdir($dest);
    }

    public function testRecursiveDirectoryRemoveUnLinksSymLinks()
    {
        $tmp            = sys_get_temp_dir();
        $basePath       = $tmp . "/n98_testdir";
        $symLinked      = $tmp . "/n98_linked";
        $symLinkedFile = $symLinked . "/symlinkme.txt";

        @mkdir($basePath, 0777, true);
        @mkdir($symLinked, 0777, true);

        touch($symLinkedFile);

        $result = @symlink($symLinked, $basePath . "/symlink");
        $this->assertTrue($result);

        $this->fileSystem->recursiveRemoveDirectory($basePath);

        $this->assertFileExists($symLinkedFile);
        $this->assertFileNotExists($basePath);
    }

    public function testRecursiveRemove()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        $folder1    = $basePath . "/folder1";
        $folder2    = $basePath . "/folder2";
        $file1      = $folder1 . "/file1.txt";
        $file2      = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath);
        $this->assertFileNotExists($basePath);
    }

    public function testRecursiveRemoveWithTrailingSlash()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        $folder1    = $basePath . "/folder1";
        $folder2    = $basePath . "/folder2";
        $file1      = $folder1 . "/file1.txt";
        $file2      = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath . "/");
        $this->assertFileNotExists($basePath);
    }

    public function testFalseIsReturnedIfDirectoryNotExist()
    {
        $this->assertFalse($this->fileSystem->recursiveRemoveDirectory("not-a-folder"));
    }

    public function testFalseIsReturnedIfDirectoryNotReadable()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        @mkdir($basePath, 0000, true);

        $this->assertFalse($this->fileSystem->recursiveRemoveDirectory($basePath));
        //cleanup
        rmdir($basePath);
    }

    public function testParentIsNotRemovedIfEmptyIsTrue()
    {
        $tmp        = sys_get_temp_dir();
        $basePath   = $tmp . "/n98_testdir";
        $folder1    = $basePath . "/folder1";
        $folder2    = $basePath . "/folder2";
        $file1      = $folder1 . "/file1.txt";
        $file2      = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath, true);
        $this->assertFileExists($basePath);
        $this->assertFileNotExists($folder1);
        $this->assertFileNotExists($folder2);
    }

    /**
     * @param int $bytes
     * @param int $decimalPlaces
     * @param string $expected
     * @dataProvider convertedBytesProvider
     */
    public function testConvertBytesToHumanReadable($bytes, $decimalPlaces, $expected)
    {
        $res = Filesystem::humanFileSize($bytes, $decimalPlaces);
        $this->assertSame($expected, $res);

    }

    /**
     * @return array
     */
    public static function convertedBytesProvider()
    {
        return array(
            array(20000000,     2,  '19.07M'),
            array(20000000,     3,  '19.073M'),
            array(2000000000,   2,  '1.86G'),
            array(2,            2,  '2.00B'),
            array(2048,         2,  '2.00K'),
        );
    }
}