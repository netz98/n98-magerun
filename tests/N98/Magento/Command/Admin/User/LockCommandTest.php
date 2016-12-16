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
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function daysProvider()
    {
        return array(
            array(1, 86400),
            array(31, 2678400),
        );
    }
}
