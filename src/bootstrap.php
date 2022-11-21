<?php

use N98\MagerunBootstrap;
if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

if (!class_exists(MagerunBootstrap::class)) {
    require_once __DIR__ . '/N98/MagerunBootstrap.php';
}

try {
    return MagerunBootstrap::createApplication();
} catch (Exception $exception) {
    printf("%s: %s\n", get_class($exception), $exception->getMessage());
    if (array_intersect(['-vvv', '-vv', '-v', '--verbose'], $argv)) {
        printf("%s\n", $exception->getTraceAsString());
    }

    exit(1);
}
