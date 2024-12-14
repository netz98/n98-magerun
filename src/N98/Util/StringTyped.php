<?php

declare(strict_types=1);

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
     * @param mixed $value
     */
    public static function parseBoolOption($value): bool
    {
        return in_array(strtolower($value), ['y', 'yes', 1, 'true']);
    }

    /**
     * @param mixed $value
     */
    public static function formatActive($value): string
    {
        if (in_array($value, [1, 'true'])) {
            return 'active';
        }

        return 'inactive';
    }
}
