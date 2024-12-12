<?php

declare(strict_types=1);

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
    public const UNICODE_CHECKMARK_CHAR = 10004;

    /**
     * @var int
     */
    public const UNICODE_CROSS_CHAR = 10006;

    /**
     * @var int
     */
    public const UNICODE_WHITE_SQUARE_CHAR = 9633;

    /**
     * @param int|array $codes
     */
    public static function convertInteger(...$codes): string
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
