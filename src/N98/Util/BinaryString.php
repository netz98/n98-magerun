<?php

declare(strict_types=1);

namespace N98\Util;

/**
 * Class BinaryString
 *
 * @package N98\Util
 */
class BinaryString
{
    public static function trimExplodeEmpty(string $delimiter, string $string): array
    {
        /** @var list<string> $array */
        $array = explode($delimiter, $string);
        foreach ($array as $key => &$data) {
            $data = trim($data);
            if ($data === '' || $data === '0') {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @deprecated
     * @see str_starts_with()
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        trigger_error(__METHOD__ . ' is obsolete, use str_starts_with', E_USER_DEPRECATED);
        return str_starts_with($haystack, $needle);
    }

    /**
     * @deprecated
     * @see str_ends_with()
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        trigger_error(__METHOD__ . ' is obsolete, use str_ends_with()', E_USER_DEPRECATED);
        return str_ends_with($haystack, $needle);
    }
}
