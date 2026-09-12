<?php

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__)
    ->exclude([
        'config',
        'var',
        'public/bundles',
        'public/build',
        'tests/Support',
        'vendor',
        'assets',
    ])
    ->notPath([
        'public/index.php',
        'importmap.php',
    ])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP8x5Migration:risky' => true,
        'linebreak_after_opening_tag' => true,
        'mb_str_functions' => true,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'phpdoc_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'blank_line_between_import_groups' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache');
