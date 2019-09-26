<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use RdKafka\Message as VendorMessage;

/**
 * @deprecated Use ProduceMessageTransformer and/or ConsumeMessageTransformer instead
 */
class JsonSerializer implements Serializer
{
    public function toString(RdKafkaMessage $message): string
    {
        $json = json_encode([
            'body' => $message->getBody(),
            'properties' => $message->getProperties(),
            'headers' => $message->getHeaders(),
        ]);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $json;
    }

    public function toMessage(string $string): RdKafkaMessage
    {
        $data = json_decode($string, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return new RdKafkaMessage($data['body'], $data['properties'], $data['headers']);
    }
}
