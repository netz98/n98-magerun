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
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
