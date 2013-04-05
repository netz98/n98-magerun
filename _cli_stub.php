#!/usr/bin/env php
<?php

$autoloader = require_once __DIR__ . '/src/bootstrap.php';

$application = new N98\Magento\Application($autoloader, true);
$application->run();

__HALT_COMPILER();