<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        #__DIR__ . '/tests',
    ])
    ->withSkip([
        MakeInheritedMethodVisibilitySameAsParentRector::class => [
            __DIR__ . '/src/N98/Magento/Application.php',
        ],
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/src/N98/Magento/Initializer.php',
        ],
    ])
    ->withPreparedSets(
        true,
        true,
        true,
        false,
        true,
        true,
        true,
        true,
        true,
        true,
        true,
        true,
        false,
        true,
        true,
        true,
        true
    )
    ->withTypeCoverageLevel(0);
