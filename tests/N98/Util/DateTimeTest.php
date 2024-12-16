<?php

declare(strict_types=1);

namespace N98\Util;

use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeZone;

final class DateTimeTest extends TestCase
{
    /**
     * @param string $expected
     * @dataProvider getDifferenceAsStringProvider
     */
    public function testGetDifferenceAsString(DateTime $time1, DateTime $time2, $expected)
    {
        $dateTime = new \N98\Util\DateTime();
        $this->assertSame($expected, $dateTime->getDifferenceAsString($time1, $time2));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function getDifferenceAsStringProvider(): \Iterator
    {
        yield [new DateTime('2013-12-01', new DateTimeZone('UTC')), new DateTime('2013-12-01', new DateTimeZone('UTC')), '0'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 00:00:01', new DateTimeZone('UTC')), '1s'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 00:01:01', new DateTimeZone('UTC')), '1m 1s'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-01 01:01:01', new DateTimeZone('UTC')), '1h 1m 1s'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2013-12-02 01:01:01', new DateTimeZone('UTC')), '1d 1h 1m 1s'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2014-01-02 01:01:01', new DateTimeZone('UTC')), '1M 1d 1h 1m 1s'];
        yield [new DateTime('2013-12-01 00:00:00', new DateTimeZone('UTC')), new DateTime('2015-01-02 01:01:01', new DateTimeZone('UTC')), '1Y 1M 1d 1h 1m 1s'];
    }
}
