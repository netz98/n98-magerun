<?php

declare(strict_types=1);

namespace N98\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class BinaryStringTest
 *
 * @covers \N98\Util\BinaryString
 */
final class BinaryStringTest extends TestCase
{
    /**
     * @param string $string
     * @param string $delimiter
     * @param array  $expected
     * @dataProvider trimExplodeEmptyProvider
     */
    public function testTrimExplodeEmpty($delimiter, $string, $expected)
    {
        $this->assertEqualsCanonicalizing($expected, BinaryString::trimExplodeEmpty($delimiter, $string));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public function trimExplodeEmptyProvider(): \Iterator
    {
        yield [',', 'Foo,Bar', ['Foo', 'Bar']];
        yield ['#', ' Foo# Bar', ['Foo', 'Bar']];
        yield [',', ',,Foo, Bar,,', ['Foo', 'Bar']];
    }
}
