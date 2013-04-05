#!/usr/bin/env php
<?php

Phar::mapPhar('n98-magerun.phar');

$autoloader = require_once 'phar://n98-magerun.phar/src/bootstrap.php';

$application = new N98\Magento\Application($autoloader, true);
$application->run();

__HALT_COMPILER();