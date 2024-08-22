<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in('.');

return (new Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        'array_syntax' => ['syntax' => 'short'],
        'modernize_types_casting' => true,
        'logical_operators' => true,
        'single_quote' => true,
    ]);
