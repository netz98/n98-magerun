<?php

namespace N98\Util;

class DateTime
{
    /**
     * Returns a readable string with time difference
     *
     * @param \DateTime $time1
     * @param \DateTime $time2
     *
     * @return string
     */
    public function getDifferenceAsString(\DateTime $time1, \DateTime $time2)
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

        $differenceString = (($years) ? $years . 'Y ' : '')
                          . (($months) ? $months. 'M ' : '')
                          . (($days) ? $days. 'd ' : '')
                          . (($hours) ? $hours. 'h ' : '')
                          . (($minutes) ? $minutes . 'm ' : '')
                          . (($seconds) ? $seconds . 's' : '');

        return trim($differenceString);
    }
}