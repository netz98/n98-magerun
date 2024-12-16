<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use N98\Magento\TestApplication;
use PHPUnit\Framework\MockObject\MockObject;

// shim for phpunit mock-objects (deprecated) forward compatibility
if (!interface_exists(MockObject::class)) {
    class_alias('PHPUnit_Framework_MockObject_MockObject', MockObject::class);
}

$base = TestApplication::getTestMagentoRootFromEnvironment('N98_MAGERUN_TEST_MAGENTO_ROOT', '.n98-magerun');
if (false === $base) {
    unset($base);
    return;
}

@session_start();
/** @var ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->setUseIncludePath(true);

$paths = [
    $base . '/app/code/local',
    $base . '/app/code/community',
    $base . '/app/code/core',
    $base . '/lib',
];
set_include_path(implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR . get_include_path());
unset($paths, $base);
