#!/usr/bin/php
<?php

require_once 'phar://' . __FILE__ . '/vendor/autoload.php';

$application = new N98\Magento\Application();
$application->run();

__HALT_COMPILER();