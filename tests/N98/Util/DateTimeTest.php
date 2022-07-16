<?php

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeZone;
class DateTimeTest extends TestCase
{
    /**
     * @test
     * @param \DateTime $time1
     * @param \DateTime $time2
     * @param string $expected
     * @dataProvider getDifferenceAsStringProvider
     */
    public function getDifferenceAsString(DateTime $time1, DateTime $time2, $expected)
    {
        $dateTime = new DateTime();
        self::assertEquals($expected, $dateTime->getDifferenceAsString($time1, $time2));
    }

    /**
     * @return array
     */
    public static function getDifferenceAsStringProvider()
    {
        return [[new DateTime('2013-12-01', new DateTimeZone('UTC')), new DateTime('2013-12-01', new DateTimeZone('UTC')), '0'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 00:00:01', new DateTimeZone('UTC')), '1s'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 00:01:01', new DateTimeZone('UTC')), '1m 1s'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 01:01:01', new DateTimeZone('UTC')), '1h 1m 1s'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-02 01:01:01', new DateTimeZone('UTC')), '1d 1h 1m 1s'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2014-01-02 01:01:01', new DateTimeZone('UTC')), '1M 1d 1h 1m 1s'], [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2015-01-02 01:01:01', new DateTimeZone('UTC')), '1Y 1M 1d 1h 1m 1s']];
    }
}
