<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

class JsonSerializer implements Serializer
{
    public function toString(WampMessage $message): string
    {
        $json = json_encode([
            'body' => $message->getBody(),
            'properties' => $message->getProperties(),
            'headers' => $message->getHeaders(),
        ]);

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf('The malformed json given. Error %s and message %s', json_last_error(), json_last_error_msg()));
        }

        return $json;
    }

    public function toMessage(string $string): WampMessage
    {
        $data = json_decode($string, true);
        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf('The malformed json given. Error %s and message %s', json_last_error(), json_last_error_msg()));
        }

        return new WampMessage($data['body'], $data['properties'], $data['headers']);
    }
}
