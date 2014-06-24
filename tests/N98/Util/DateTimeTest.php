<?php

namespace N98\Util;

class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @param \DateTime $time1
     * @param \DateTime $time2
     * @param string $expected
     * @dataProvider getDifferenceAsStringProvider
     */
    public function getDifferenceAsString(\DateTime $time1, \DateTime $time2, $expected)
    {
        $this->assertEquals($expected, DateTime::getDifferenceAsString($time1, $time2));
    }

    /**
     * @return array
     */
    public static function getDifferenceAsStringProvider()
    {
        return array(
            array(
                new \DateTime('2013-12-01'),
                new \DateTime('2013-12-01'),
                '0'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2013-12-01 00:00:01'),
                '1s'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2013-12-01 00:01:01'),
                '1m 1s'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2013-12-01 01:01:01'),
                '1h 1m 1s'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2013-12-02 01:01:01'),
                '1d 1h 1m 1s'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2014-01-02 01:01:01'),
                '1M 2d 1h 1m 1s'
            ),
            array(
                new \DateTime('2013-12-01 00:00:00'),
                new \DateTime('2015-01-02 01:01:01'),
                '1Y 1M 2d 1h 1m 1s'
            ),
        );
    }
}