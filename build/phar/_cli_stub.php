#!/usr/bin/env php
<?php

Phar::mapPhar('n98-magerun.phar');

if (extension_loaded('suhosin')) {
    $suhosin = ini_get('suhosin.executor.include.whitelist');
    $suhosinBlacklist = ini_get('suhosin.executor.include.blacklist');
    if (false === stripos($suhosin, 'phar') && (!$suhosinBlacklist || false !== stripos($suhosinBlacklist, 'phar'))) {
        fwrite(STDERR, implode(PHP_EOL, array(
            'The suhosin.executor.include.whitelist setting is incorrect.',
            'Add the following to the end of your `php.ini` or suhosin.ini (Example path [for Debian]: /etc/php5/cli/conf.d/suhosin.ini):',
            '    suhosin.executor.include.whitelist = phar '.$suhosin,
            ''
        )));
        exit(1);
    }
}

$application = require_once 'phar://n98-magerun.phar/src/bootstrap.php';
$application->setPharMode(true);
$application->run();

__HALT_COMPILER();
