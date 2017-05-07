<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class VerifyOrDie
 *
 * @package N98\Util
 */
class VerifyOrDie
{
    /**
     * Portable basename
     *
     * @param string $basename
     * @param string $message [optional]
     * @return string
     */
    public static function filename($basename, $message = null)
    {
        static::argumentType('basename', 'string', $basename);
        null === $message || static::argumentType('message', 'string', $message);

        # a filename must at least contain a single character
        if (!strlen($basename)) {
            self::violation($message ?: 'Filename is zero-length string');
        }

        # no control characters, no posix forbidden ones, no windows forbidden ones and no spaces - and not empty
        $pattern = '~^[^\x00-\x1F\x7F/<>:"\\|?* ]+$~';
        if (!preg_match($pattern, $basename)) {
            self::violation($message ?: sprintf("Filename %s is not portable", var_export($basename, true)));
        }

        if ('-' === $basename[0]) {
            self::violation($message ?: sprintf("Filename %s starts with a dash", var_export($basename, true)));
        }

        return $basename;
    }

    /**
     * @param string $name
     * @param string $internalType
     * @param mixed $subject
     */
    public static function argumentType($name, $internalType, $subject)
    {
        $actual = gettype($subject);
        if ($actual !== $internalType) {
            throw new InvalidArgumentException(
                sprintf('Parameter %s must be of type %s, %s given', $name, $internalType, $actual)
            );
        }
    }

    /**
     * @param string $message
     */
    private static function violation($message)
    {
        throw new RuntimeException($message);
    }
}
