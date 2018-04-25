<?php

namespace Enqueue\RdKafka;

class JsonSerializer implements Serializer
{
    /**
     * {@inheritdoc}
     */
    public function toString(RdKafkaMessage $message)
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

    /**
     * {@inheritdoc}
     */
    public function toMessage($string)
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
