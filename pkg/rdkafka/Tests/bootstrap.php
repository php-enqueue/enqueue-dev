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

// Pretend that a high version was installed, opposed to 0.8.5 from kwn/php-rdkafka-stubs
const RD_KAFKA_VERSION = 42424242;

include_once $kafkaStubsDir.'/stubs/constants.php';
include_once $kafkaStubsDir.'/stubs/functions.php';
