<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use RuntimeException;
/**
 * Class FilesystemTest
 * @package N98\Util
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @covers N98\Util\Filesystem
 */
class FilesystemTest extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    protected function setUp(): void
    {
        $this->fileSystem = new Filesystem();
    }

    public function testRecursiveCopy()
    {
        $this->expectException(RuntimeException::class);
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $folder1 = $basePath . "/folder1";
        $folder2 = $basePath . "/folder2";
        $file1 = $folder1 . "/file1.txt";
        $file2 = $folder2 . "/file2.txt";
        $dest = sys_get_temp_dir() . "/n98_copy_dest";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveCopy($basePath, $dest);
        self::assertFileExists($dest . "/folder1/file1.txt");
        self::assertFileExists($dest . "/folder2/file2.txt");

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

        self::assertFileNotExists($dest . "/folder1/file1.txt");
        self::assertFileNotExists($dest);

        if (!is_dir($tmp . '/a')) {
            mkdir($tmp . '/a');
        }
        touch($tmp . '/file1.txt');
        $this->fileSystem->recursiveCopy($tmp . '/a', $tmp . '/file1.txt');
        unlink($tmp . '/file1.txt');
        rmdir($tmp . '/a');
    }

    public function testRecursiveCopyWithBlacklist()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $folder1 = $basePath . "/folder1";
        $folder2 = $basePath . "/folder2";
        $file1 = $folder1 . "/file1.txt";
        $ignoreMe = $folder1 . "/ignore.me";
        $file2 = $folder2 . "/file2.txt";
        $dest = sys_get_temp_dir() . "/n98_copy_dest";
        $this->fileSystem->recursiveRemoveDirectory($dest, true);

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($ignoreMe);
        touch($file2);

        $this->fileSystem->recursiveCopy($basePath, $dest, ['ignore.me']);
        self::assertFileExists($dest . "/folder1/file1.txt");
        self::assertFileNotExists($dest . "/folder1/ignore.me");
        self::assertFileExists($dest . "/folder2/file2.txt");

        //cleanup
        unlink($file1);
        unlink($ignoreMe);
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

    /**
     * @requires function symlink
     */
    public function testRecursiveDirectoryRemoveUnLinksSymLinks()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $symLinked = $tmp . "/n98_linked";
        $symLinkedFile = $symLinked . "/symlinkme.txt";

        @mkdir($basePath, 0777, true);
        @mkdir($symLinked, 0777, true);

        touch($symLinkedFile);

        $result = @symlink($symLinked, $basePath . "/symlink");
        self::assertTrue($result);

        $this->fileSystem->recursiveRemoveDirectory($basePath);

        self::assertFileExists($symLinkedFile);
        self::assertFileNotExists($basePath);
    }

    public function testRecursiveRemove()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $folder1 = $basePath . "/folder1";
        $folder2 = $basePath . "/folder2";
        $file1 = $folder1 . "/file1.txt";
        $file2 = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath);
        self::assertFileNotExists($basePath);
    }

    public function testRecursiveRemoveWithTrailingSlash()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $folder1 = $basePath . "/folder1";
        $folder2 = $basePath . "/folder2";
        $file1 = $folder1 . "/file1.txt";
        $file2 = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath . "/");
        self::assertFileNotExists($basePath);
    }

    public function testFalseIsReturnedIfDirectoryNotExist()
    {
        self::assertFalse($this->fileSystem->recursiveRemoveDirectory("not-a-folder"));
    }

    public function testFalseIsReturnedIfDirectoryNotReadable()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir-never-existed";

        self::assertFalse($this->fileSystem->recursiveRemoveDirectory($basePath));
    }

    public function testParentIsNotRemovedIfEmptyIsTrue()
    {
        $tmp = sys_get_temp_dir();
        $basePath = $tmp . "/n98_testdir";
        $folder1 = $basePath . "/folder1";
        $folder2 = $basePath . "/folder2";
        $file1 = $folder1 . "/file1.txt";
        $file2 = $folder2 . "/file2.txt";

        @mkdir($folder1, 0777, true);
        @mkdir($folder2, 0777, true);
        touch($file1);
        touch($file2);

        $this->fileSystem->recursiveRemoveDirectory($basePath, true);
        self::assertFileExists($basePath);
        self::assertFileNotExists($folder1);
        self::assertFileNotExists($folder2);
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
        self::assertSame($expected, $res);
    }

    /**
     * @return array
     */
    public static function convertedBytesProvider()
    {
        return [[20_000_000, 2, '19.07M'], [20_000_000, 3, '19.073M'], [2_000_000_000, 2, '1.86G'], [2, 2, '2.00B'], [2048, 2, '2.00K']];
    }
}
