<?php

require_once __DIR__ . '/vendor/autoload.php';

$application = new N98\Magento\Application();
$application->run();

__halt_compiler();