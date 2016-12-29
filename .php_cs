<?php

$finder = Symfony\CS\Finder::create()
    ->in(__DIR__)
    ->notPath('vendor')
;

return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        'ordered_use',
        'no_blank_lines_before_namespace',
        'short_array_syntax',
        'unused_use',
        'phpdoc_order',


    ])
    ->finder($finder)
;

