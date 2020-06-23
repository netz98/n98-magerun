<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase as TestCase;
use RuntimeException;

/**
 * Class VerifyOrDieTest
 *
 * @package N98\Util
 */
class VerifyOrDieTest extends TestCase
{
    /**
     * @test a portable filename passes
     */
    public function portableFilename()
    {
        $this->assertSame("example.txt", VerifyOrDie::filename("example.txt"));

        $this->assertSame(".hidden", VerifyOrDie::filename(".hidden"));
    }

    /**
     * @test user-message for verification
     */
    public function userMessage()
    {
        $message = sprintf('Database name %s is not portable', var_export('-fail', true));
        try {
            VerifyOrDie::filename('-fail', $message);
            $this->fail('An expected exception has not been thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    /**
     * @test a filename must have at least one byte
     * @expectedException RuntimeException
     * @expectedExceptionMessage Filename is zero-length string
     */
    public function zeroLengthFilename()
    {
        VerifyOrDie::filename('');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Parameter basename must be of type string, NULL given
     */
    public function invalidArugment()
    {
        VerifyOrDie::filename(null);
    }

    /**
     * @test a filename must not start with a dash
     * @expectedException RuntimeException
     * @expectedExceptionMessage Filename '-rf' starts with a dash
     */
    public function startWithDashFilename()
    {
        VerifyOrDie::filename('-rf');
    }

    /**
     * @test
     * @dataProvider provideNonPortableFilenames
     * @expectedException RuntimeException
     * @expectedExceptionMessage is not portable
     */
    public function nonPortableFilenameThrowsException($filename)
    {
        VerifyOrDie::filename($filename);
    }

    /**
     * @see nonPortableFilenameThrowsException
     */
    public function provideNonPortableFilenames()
    {
        return array(
            array('no-slash-/-in.there'),
            array('windoze-limits-<>:"/\\|?*'),
            array('lets-keep-spaces   out'),
        );
    }
}
