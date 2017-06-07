<?php

namespace N98\Util\Faker\Provider;

class Internet extends \Faker\Provider\Internet
{
    // Reduce the chance of conflicts.
    protected static $userNameFormats = array(
        '{{lastName}}.{{firstName}}.######',
        '{{firstName}}.{{lastName}}.######',
        '{{firstName}}.######',
        '?{{lastName}}.######',
    );
}
