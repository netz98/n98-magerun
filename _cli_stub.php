#!/usr/bin/env php
<?php

$autoloader = require_once 'phar://' . __FILE__ . '/vendor/autoload.php';

$application = new N98\Magento\Application($autoloader);
$application->run();

__HALT_COMPILER();