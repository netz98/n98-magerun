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
    public static function parseBoolOption(string $value): bool
    {
        return in_array(strtolower($value), ['y', 'yes', 1, 'true']);
    }

    public static function formatActive(string $value): string
    {
        if (in_array($value, [1, 'true'])) {
            return 'active';
        }

        return 'inactive';
    }
}
