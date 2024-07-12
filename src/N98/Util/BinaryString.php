<?php

namespace N98\Util;

class BinaryString
{
    /**
     * @param $delimiter
     * @param $string
     * @return array
     */
    public static function trimExplodeEmpty($delimiter, $string)
    {
        $array = explode($delimiter, $string);
        foreach ($array as $key => &$data) {
            $data = trim($data);
            if (empty($data)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     *
     * @deprecated use str_starts_with() instead
     */
    public static function startsWith($haystack, $needle)
    {
        trigger_error(__METHOD__ . ' is obsolete, use str_starts_with', E_USER_DEPRECATED);
        return str_starts_with($haystack, $needle);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     *
     * @deprecated use str_ends_with() instead
     */
    public static function endsWith($haystack, $needle)
    {
        trigger_error(__METHOD__ . ' is obsolete, use str_ends_with()', E_USER_DEPRECATED);
        return str_ends_with($haystack, $needle);
    }
}
