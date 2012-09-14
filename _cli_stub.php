#!/usr/bin/php
<?php

define('N98_MAGERUN_ROOT', __DIR__);

$autoloader = require_once 'phar://' . __FILE__ . '/vendor/autoload.php';

$application = new N98\Magento\Application($autoloader);
$application->run();

__HALT_COMPILER();