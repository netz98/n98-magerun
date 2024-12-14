<?php

declare(strict_types=1);

namespace N98\Util;

use DateTime as PhpDateTime;

/**
 * Class DateTime
 *
 * @package N98\Util
 */
class DateTime
{
    /**
     * Human-readable string with time difference
     * @deprecated use Carbon
     * @todo SR carbon
     */
    public static function difference(PhpDateTime $time1, PhpDateTime $time2): string
    {
        if ($time1 == $time2) {
            return '0';
        }

        $dateInterval   = $time1->diff($time2);
        $years          = $dateInterval->format('%y');
        $months         = $dateInterval->format('%m');
        $days           = $dateInterval->format('%d');
        $hours          = $dateInterval->format('%h');
        $minutes        = $dateInterval->format('%i');
        $seconds        = $dateInterval->format('%s');

        $differenceString = trim(
            ($years ? $years . 'Y ' : '')
            . ($months ? $months . 'M ' : '')
            . ($days ? $days . 'd ' : '')
            . ($hours ? $hours . 'h ' : '')
            . ($minutes ? $minutes . 'm ' : '')
            . ($seconds ? $seconds . 's ' : ''),
        );

        if ($differenceString === '') {
            $time1format    = (int) $time1->format('u');
            $time2format    = (int) $time2->format('u');
            $milliseconds   = max(0, $time2format / 1000 - $time1format / 1000);
            $differenceString = $milliseconds ? sprintf('%0.2fms', $milliseconds) : '';
        }

        return $differenceString;
    }

    /**
     * Returns a readable string with time difference
     */
    public function getDifferenceAsString(PhpDateTime $time1, PhpDateTime $time2): string
    {
        return self::difference($time1, $time2);
    }
}
