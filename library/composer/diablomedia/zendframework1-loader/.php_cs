<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->files()
    ->in(__DIR__)
    ->notName('ZFAutoloadParseError.php')
    ->notName('AutoloaderClosure.php')
    ->notName('Demo.php')
;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@PHPUnit60Migration:risky' => true,
        'binary_operator_spaces' => ['align_double_arrow' => true, 'align_equals' => true],
        'single_quote' => true,
        'array_syntax' => ['syntax' => 'long'],
        'concat_space' => ['spacing' => 'one'],
        'psr0' => true
    ])
    ->setUsingCache(true)
    ->setFinder($finder);
;
