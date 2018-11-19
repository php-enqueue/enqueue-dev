<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => array('syntax' => 'short'),
        'combine_consecutive_unsets' => true,
        // one should use PHPUnit methods to set up expected exception instead of annotations
        'general_phpdoc_annotation_remove' => array('expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp'),
        'heredoc_to_nowdoc' => true,
        'no_extra_consecutive_blank_lines' => array('break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block'),
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'psr4' => true,
        'strict_param' => true,
        'native_function_invocation' => false,
    ))
    ->setCacheFile(getenv('TRAVIS') ? getenv('HOME') . '/php-cs-fixer/.php-cs-fixer' : __DIR__.'/var/.php_cs.cache')
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->name('/\.php$/')
            ->in(__DIR__)
    )
;
