<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\TestCase;

class LockCommandTest extends TestCase
{
    /**
     * Ensure that days are correctly converted to seconds
     *
     * @dataProvider daysProvider
     *
     * @param int $days
     * @param int $expected
     */
    public function testConvertDaysToSeconds($days, $expected)
    {
        $command = new LockCommand();
        $result = $command->daysToSeconds($days);
        self::assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function daysProvider()
    {
        return [[1, 86400], [31, 2678400]];
    }
}
