#!/usr/bin/env php
<?php

Phar::mapPhar('n98-magerun.phar');

$application = require_once 'phar://n98-magerun.phar/src/bootstrap.php';
$application->setPharMode(true);
$application->run();

__HALT_COMPILER();