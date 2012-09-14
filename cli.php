<?php

define('N98_MAGERUN_ROOT', __DIR__);

$autoloader = require_once __DIR__ . '/vendor/autoload.php';

$application = new N98\Magento\Application($autoloader);
$application->run();

__halt_compiler();