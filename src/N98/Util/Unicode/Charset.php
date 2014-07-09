<?php

namespace N98\Util\Unicode;

class Charset
{
    /**
     * @param int|array $codes
     * @return string
     */
    public static function convertInteger($codes) {
        if (is_scalar($codes)) {
            $codes= func_get_args();
        }

        $str= '';
        foreach ($codes as $code) {
            $str .= html_entity_decode('&#'.$code.';', ENT_NOQUOTES, 'UTF-8');
        }

        return $str;
    }
}