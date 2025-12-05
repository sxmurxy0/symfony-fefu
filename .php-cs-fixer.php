<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer-cache')
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'line_ending' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => true,
        ],
        'trailing_comma_in_multiline' => [
            'elements' => []
        ],
        'operator_linebreak' => [
            'only_booleans' => true,
            'position' => 'end'
        ]
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
