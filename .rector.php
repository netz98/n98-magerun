<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        MakeInheritedMethodVisibilitySameAsParentRector::class => [
            __DIR__ . '/src/N98/Magento/Application.php',
        ],
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/src/N98/Magento/Initializer.php',
        ],
        RemoveUnusedPrivateMethodRector::class => [
            __DIR__ . '/tests/N98/Magento/Command/System/Setup/IncrementalCommandTest.php',
        ],
        AssertEqualsToSameRector::class => [
            __DIR__ . 'tests/N98/Util/Console/Helper/DatabaseHelperTest.php',
        ],
         PrivatizeFinalClassMethodRector::class => [
            __DIR__ . '/tests/N98/Magento/Command/System/Setup/IncrementalCommandTest.php',
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
        false,
        true,
        true,
        false,
        true,
        true
    )
    ->withTypeCoverageLevel(0);
