<?php

namespace N98\Util\Unicode;

class CharsetTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertInteger()
    {

        $this->assertEquals('✖', Charset::convertInteger(Charset::UNICODE_CROSS_CHAR));
        $this->assertEquals('✔', Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR));
    }
}
