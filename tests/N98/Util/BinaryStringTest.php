<?php

namespace N98\Util;

use PHPUnit\Framework\TestCase;
/**
 * Class BinaryStringTest
 *
 * @covers \N98\Util\BinaryString
 */
class BinaryStringTest extends TestCase
{
    /**
     * @test
     * @param string $string
     * @param string $delimiter
     * @param array  $expected
     * @dataProvider trimExplodeEmptyProvider
     */
    public function trimExplodeEmpty($delimiter, $string, $expected)
    {
        self::assertEqualsCanonicalizing($expected, BinaryString::trimExplodeEmpty($delimiter, $string));
    }

    /**
     * @return array
     */
    public function trimExplodeEmptyProvider()
    {
        return [[',', 'Foo,Bar', ['Foo', 'Bar']], ['#', ' Foo# Bar', ['Foo', 'Bar']], [',', ',,Foo, Bar,,', ['Foo', 'Bar']]];
    }

    /**
     * @test
     */
    public function startsWith()
    {
        self::assertTrue(BinaryString::startsWith('Foo', 'Foo'));
        self::assertTrue(BinaryString::startsWith('Foo123', 'Foo'));
        self::assertFalse(BinaryString::startsWith(' Foo123', 'Foo'));
    }

    /**
     * @test
     */
    public function endsWith()
    {
        self::assertTrue(BinaryString::endsWith('Foo', 'Foo'));
        self::assertTrue(BinaryString::endsWith('Foo123', '123'));
        self::assertFalse(BinaryString::endsWith(' Foo123 ', '123'));
    }
}
