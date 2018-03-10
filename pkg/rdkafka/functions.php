<?php

namespace Enqueue\RdKafka;

function json_encode($value, $options = 0, $depth = 512)
{
    $encoded = \json_encode($value, $options, $depth);

    if (JSON_ERROR_NONE !== \json_last_error()) {
        throw new \InvalidArgumentException(sprintf(
            'Could not encode value into json. Error %s and message %s',
            \json_last_error(),
            \json_last_error_msg()
        ));
    }

    return $encoded;
}

function json_decode($string, $options = 0, $depth = 512)
{
    if (!\is_string($string)) {
        throw new \InvalidArgumentException(sprintf(
            'Accept only string argument but got: "%s"',
            \is_object($string) ? \get_class($string) : \gettype($string)
        ));
    }

    // PHP7 fix - empty string and null cause syntax error
    if (empty($string)) {
        return null;
    }

    $decoded = \json_decode($string, true, $depth, $options);
    if (JSON_ERROR_NONE !== \json_last_error()) {
        throw new \InvalidArgumentException(sprintf(
            'The malformed json given. Error %s and message %s',
            \json_last_error(),
            \json_last_error_msg()
        ));
    }

    return $decoded;
}
