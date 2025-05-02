<?php

if (empty($argv[1])) {
    throw new LogicException('The new symfony version must be provided');
}

$newVersion = $argv[1];

$composer = trim(file_get_contents(__DIR__.'/../composer.json'));

$updatedComposer = preg_replace('/"symfony\/(.*)": ".*"/', '"symfony/$1": "'.$newVersion.'"', $composer).\PHP_EOL;
echo $updatedComposer.\PHP_EOL;

file_put_contents(__DIR__.'/../composer.json', $updatedComposer);
