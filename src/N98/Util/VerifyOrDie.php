<?php

declare(strict_types=1);

namespace N98\Util;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class VerifyOrDie
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class VerifyOrDie
{
    /**
     * Portable basename
     */
    public static function filename(?string $basename, ?string $message = null): string
    {
        static::argumentType('basename', 'string', $basename);
        if (null !== $message) {
            static::argumentType('message', 'string', $message);
        }

        # a filename must at least contain a single character
        if ($basename === '') {
            self::violation($message !== null && $message !== '' && $message !== '0' ? $message : 'Filename is zero-length string');
        }

        # no control characters, no posix forbidden ones, no windows forbidden ones and no spaces - and not empty
        $pattern = '~^[^\x00-\x1F\x7F/<>:"\\|?* ]+$~';
        if (in_array(preg_match($pattern, $basename), [0, false], true)) {
            self::violation($message !== null && $message !== '' && $message !== '0' ? $message : sprintf('Filename %s is not portable', var_export($basename, true)));
        }

        if ('-' === $basename[0]) {
            self::violation($message !== null && $message !== '' && $message !== '0' ? $message : sprintf('Filename %s starts with a dash', var_export($basename, true)));
        }

        return $basename;
    }

    /**
     * @param mixed $subject
     */
    public static function argumentType(string $name, string $internalType, $subject): void
    {
        $actual = gettype($subject);
        if ($actual !== $internalType) {
            throw new InvalidArgumentException(
                sprintf('Parameter %s must be of type %s, %s given', $name, $internalType, $actual),
            );
        }
    }

    private static function violation(string $message): void
    {
        throw new RuntimeException($message);
    }
}
