<?php

$application = require_once __DIR__ . '/../../src/bootstrap.php';
$application->init();
$application->detectMagento();
if ($application->initMagento()) {
    echo <<<WELCOME
===========================
MAGENTO INTERACTIVE CONSOLE
===========================
WELCOME;
    echo PHP_EOL . PHP_EOL . 'Initialized Magento (' . \Mage::getVersion() . ')' . PHP_EOL . PHP_EOL;
} else {
    echo "FATAL: Magento could not be initialized." . PHP_EOL;
}
