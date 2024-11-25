<?php

declare(strict_types=1);

namespace N98\Util\Faker\Provider;

/**
 * Class Internet
 *
 * @package N98\Util\Faker\Provider
 */
class Internet extends \Faker\Provider\Internet
{
    /**
     * Reduce the chance of conflicts.
     *
     * @var array $userNameFormats
     */
    protected static $userNameFormats = [
        '{{lastName}}.{{firstName}}.######',
        '{{firstName}}.{{lastName}}.######',
        '{{firstName}}.######',
        '?{{lastName}}.######',
    ];
}
