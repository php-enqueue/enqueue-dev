<?php

if (extension_loaded('rdkafka')) {
    return;
}

$kafkaStubsDir = __DIR__.'/../vendor/kwn/php-rdkafka-stubs';
if (false == file_exists($kafkaStubsDir)) {
    $kafkaStubsDir = __DIR__.'/../../../vendor/kwn/php-rdkafka-stubs';
    if (false == file_exists($kafkaStubsDir)) {
        throw new \LogicException('The kafka extension is not loaded and stubs could not be found as well');
    }
}

include_once $kafkaStubsDir.'/stubs/constants.php';
include_once $kafkaStubsDir.'/stubs/functions.php';
