<?php

declare(strict_types=1);

/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class VerifyOrDieTest
 *
 * @package N98\Util
 */
final class VerifyOrDieTest extends TestCase
{
    public function testPortableFilename()
    {
        $this->assertSame('example.txt', VerifyOrDie::filename('example.txt'));

        $this->assertSame('.hidden', VerifyOrDie::filename('.hidden'));
    }

    public function testUserMessage()
    {
        $message = sprintf('Database name %s is not portable', var_export('-fail', true));
        try {
            VerifyOrDie::filename('-fail', $message);
            self::fail('An expected exception has not been thrown.');
        } catch (RuntimeException $runtimeException) {
            $this->assertSame($message, $runtimeException->getMessage());
        }
    }

    public function testZeroLengthFilename()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filename is zero-length string');
        VerifyOrDie::filename('');
    }

    public function testInvalidArugment()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter basename must be of type string, NULL given');
        VerifyOrDie::filename(null);
    }

    public function testStartWithDashFilename()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Filename '-rf' starts with a dash");
        VerifyOrDie::filename('-rf');
    }

    /**
     * @dataProvider provideNonPortableFilenames
     */
    public function testNonPortableFilenameThrowsException($filename)
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not portable');
        VerifyOrDie::filename($filename);
    }

    /**
     * @see nonPortableFilenameThrowsException
     */
    public function provideNonPortableFilenames(): \Iterator
    {
        yield ['no-slash-/-in.there'];
        yield ['windoze-limits-<>:"/\\|?*'];
        yield ['lets-keep-spaces   out'];
    }
}
