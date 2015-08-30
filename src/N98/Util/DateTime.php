<?php

namespace N98\Util;

use DateTime as PhpDateTime;

class DateTime
{
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
        if ($time1 == $time2) {
            return '0';
        }

        $interval = $time1->diff($time2);
        $years    = $interval->format('%y');
        $months   = $interval->format('%m');
        $days     = $interval->format('%d');
        $hours    = $interval->format('%h');
        $minutes  = $interval->format('%i');
        $seconds  = $interval->format('%s');

        $differenceString
            = ($years ? $years . 'Y ' : '')
            . ($months ? $months . 'M ' : '')
            . ($days ? $days . 'd ' : '')
            . ($hours ? $hours . 'h ' : '')
            . ($minutes ? $minutes . 'm ' : '')
            . ($seconds ? $seconds . 's ' : '');

        return trim($differenceString);
    }
}
