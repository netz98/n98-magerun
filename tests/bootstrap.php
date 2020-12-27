<?php

use N98\Magento\TestApplication;

// shim for phpunit mock-objects (deprecated) forward compatibility
if (!interface_exists('PHPUnit\Framework\MockObject\MockObject')) {
    class_alias('PHPUnit_Framework_MockObject_MockObject', 'PHPUnit\Framework\MockObject\MockObject');
}

$base = TestApplication::getTestMagentoRootFromEnvironment('N98_MAGERUN_TEST_MAGENTO_ROOT', '.n98-magerun');
if (false === $base) {
    unset($base);
    return;
}

@session_start();
$loader = require __DIR__ . '/../vendor/autoload.php';
/* @var $loader \Composer\Autoload\ClassLoader */
$loader->setUseIncludePath(true);

$paths = array(
    $base . '/app/code/local',
    $base . '/app/code/community',
    $base . '/app/code/core',
    $base . '/lib',
);
set_include_path(implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR . get_include_path());
unset($paths, $base);
