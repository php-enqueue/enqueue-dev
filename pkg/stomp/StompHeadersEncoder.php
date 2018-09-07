<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

class StompHeadersEncoder
{
    const PROPERTY_PREFIX = '_property_';
    const TYPE_PREFIX = '_type_';
    const TYPE_STRING = 's';
    const TYPE_INT = 'i';
    const TYPE_FLOAT = 'f';
    const TYPE_BOOL = 'b';
    const TYPE_NULL = 'n';

    public static function encode(array $headers = [], array $properties = []): array
    {
        $encodedHeaders = self::doEncode($headers);

        foreach (self::doEncode($properties) as $key => $value) {
            $encodedHeaders[self::PROPERTY_PREFIX.$key] = $value;
        }

        return $encodedHeaders;
    }

    /**
     * Returns array [[headers], [properties]].
     */
    public static function decode(array $headers = []): array
    {
        $encodedHeaders = [];
        $encodedProperties = [];
        $prefixLength = strlen(self::PROPERTY_PREFIX);

        // separate headers/properties
        foreach ($headers as $key => $value) {
            if (0 === strpos($key, self::PROPERTY_PREFIX)) {
                $encodedProperties[substr($key, $prefixLength)] = $value;
            } else {
                $encodedHeaders[$key] = $value;
            }
        }

        $decodedHeaders = self::doDecode($encodedHeaders);
        $decodedProperties = self::doDecode($encodedProperties);

        return [$decodedHeaders, $decodedProperties];
    }

    private static function doEncode(array $headers = []): array
    {
        $encoded = [];

        foreach ($headers as $key => $value) {
            switch ($type = gettype($value)) {
                case 'string':
                    $encoded[$key] = (string) $value;
                    $encoded[self::TYPE_PREFIX.$key] = self::TYPE_STRING;

                    break;
                case 'integer':
                    $encoded[$key] = (string) $value;
                    $encoded[self::TYPE_PREFIX.$key] = self::TYPE_INT;

                    break;
                case 'double':
                    $encoded[$key] = (string) $value;
                    $encoded[self::TYPE_PREFIX.$key] = self::TYPE_FLOAT;

                    break;
                case 'NULL':
                    $encoded[$key] = '';
                    $encoded[self::TYPE_PREFIX.$key] = self::TYPE_NULL;

                    break;
                case 'boolean':
                    $encoded[$key] = $value ? 'true' : 'false';
                    $encoded[self::TYPE_PREFIX.$key] = self::TYPE_BOOL;

                    break;
                default:
                    throw new \LogicException(sprintf('Value type is not valid: "%s"', $type));
            }
        }

        return $encoded;
    }

    private static function doDecode(array $headers = []): array
    {
        $decoded = [];

        foreach ($headers as $key => $value) {
            // skip type header
            if (0 === strpos($key, self::TYPE_PREFIX)) {
                continue;
            }

            // copy value as is if here is no type header
            if (false == array_key_exists(self::TYPE_PREFIX.$key, $headers)) {
                $decoded[$key] = $value;

                continue;
            }

            switch ($headers[self::TYPE_PREFIX.$key]) {
                case self::TYPE_STRING:
                    $decoded[$key] = (string) $value;

                    break;
                case self::TYPE_INT:
                    $decoded[$key] = (int) $value;

                    break;
                case self::TYPE_FLOAT:
                    $decoded[$key] = (float) $value;

                    break;
                case self::TYPE_NULL:
                    $decoded[$key] = null;

                    break;
                case self::TYPE_BOOL:
                    $decoded[$key] = 'true' === $value;

                    break;
                default:
                    throw new \LogicException(sprintf('Type is invalid: "%s"', $value));
            }
        }

        return $decoded;
    }
}
