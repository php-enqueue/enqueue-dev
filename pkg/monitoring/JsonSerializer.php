<?php

declare(strict_types=1);

namespace Enqueue\Monitoring;

class JsonSerializer implements Serializer
{
    public function toString(Stats $stats): string
    {
        $rfClass = new \ReflectionClass($stats);

        $data = [
            'event' => $rfClass->getShortName(),
        ];

        foreach ($rfClass->getProperties() as $rfProperty) {
            $rfProperty->setAccessible(true);
            $data[$rfProperty->getName()] = $rfProperty->getValue($stats);
            $rfProperty->setAccessible(false);
        }

        $json = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $json;
    }
}
