<?php

if (!class_exists('N98\MagerunBootstrap')) {
    require_once __DIR__ . '/../shared/src/N98/MagerunBootstrap.php';
}

try {
    return N98\MagerunBootstrap::createApplication();
} catch (Exception $e) {
    printf("%s: %s\n", get_class($e), $e->getMessage());
    if (array_intersect(array('-vvv', '-vv', '-v', '--verbose'), $argv)) {
        printf("%s\n", $e->getTraceAsString());
    }
    exit(1);
}
