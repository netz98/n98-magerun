<?php

namespace N98\Util\Unicode;

use PHPUnit\Framework\TestCase;
class CharsetTest extends TestCase
{
    public function testConvertInteger()
    {
        self::assertEquals('✖', Charset::convertInteger(Charset::UNICODE_CROSS_CHAR));
        self::assertEquals('✔', Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR));
    }
}
