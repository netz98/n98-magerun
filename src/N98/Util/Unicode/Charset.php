<?php

namespace N98\Util\Unicode;

/**
 * Class Charset
 *
 * @package N98\Util\Unicode
 */
class Charset
{
    /**
     * @var int
     */
    const UNICODE_CHECKMARK_CHAR = 10004;

    /**
     * @var int
     */
    const UNICODE_CROSS_CHAR = 10006;

    /**
     * @var int
     */
    const UNICODE_WHITE_SQUARE_CHAR = 9633;

    /**
     * @param int|array $codes
     * @return string
     */
    public static function convertInteger(...$codes)
    {
        if (count($codes) === 1 && is_array($codes[0])) {
            $codes = $codes[0];
        }

        $str = '';
        foreach ($codes as $code) {
            $str .= html_entity_decode('&#' . $code . ';', ENT_NOQUOTES, 'UTF-8');
        }

        return $str;
    }
}
