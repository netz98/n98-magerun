<?php

namespace N98\Util;

use DateTime as PhpDateTime;

class DateTime
{
    /**
     * Human-readable string with time difference
     *
     * @param PhpDateTime $time1
     * @param PhpDateTime $time2
     *
     * @return string
     */
    public static function difference(PhpDateTime $time1, PhpDateTime $time2)
    {
        if ($time1 == $time2) {
            return '0';
        }

        $interval = $time1->diff($time2);
        $years = $interval->format('%y');
        $months = $interval->format('%m');
        $days = $interval->format('%d');
        $hours = $interval->format('%h');
        $minutes = $interval->format('%i');
        $seconds = $interval->format('%s');

        $differenceString = trim(
            ($years ? $years . 'Y ' : '')
            . ($months ? $months . 'M ' : '')
            . ($days ? $days . 'd ' : '')
            . ($hours ? $hours . 'h ' : '')
            . ($minutes ? $minutes . 'm ' : '')
            . ($seconds ? $seconds . 's ' : '')
        );

        if (!strlen($differenceString)) {
            $milliseconds = max(0, $time2->format("u") / 1000 - $time1->format("u") / 1000);
            $differenceString = $milliseconds ? sprintf('%0.2fms', $milliseconds) : '';
        }

        return $differenceString;
    }

    /**
     * Returns a readable string with time difference
     *
     * @param PhpDateTime $time1
     * @param PhpDateTime $time2
     *
     * @return string
     */
    public function getDifferenceAsString(PhpDateTime $time1, PhpDateTime $time2)
    {
        return self::difference($time1, $time2);
    }
}
