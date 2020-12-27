<?php

namespace N98\Util\Unicode;

class CharsetTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertInteger()
    {
        self::assertEquals('✖', Charset::convertInteger(Charset::UNICODE_CROSS_CHAR));
        self::assertEquals('✔', Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR));
    }
}
