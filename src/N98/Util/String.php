<?php

namespace N98\Util;

class String
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
}