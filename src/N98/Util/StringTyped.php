<?php

namespace N98\Util;

/**
 * StringTyped String formatter / parser
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
abstract class StringTyped
{
    /**
     * @param string $value
     * @return bool
     */
    public static function parseBoolOption($value)
    {
        return in_array(strtolower($value), ['y', 'yes', 1, 'true']);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function formatActive($value)
    {
        if (in_array($value, [1, 'true'])) {
            return 'active';
        }

        return 'inactive';
    }
}
