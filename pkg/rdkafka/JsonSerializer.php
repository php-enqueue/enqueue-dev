<?php

namespace Enqueue\RdKafka;

class JsonSerializer implements Serializer
{
    /**
     * {@inheritdoc}
     */
    public function toString(RdKafkaMessage $message)
    {
        return json_encode(
            [
                'body' => $message->getBody(),
                'properties' => $message->getProperties(),
                'headers' => $message->getHeaders(),
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toMessage($string)
    {
        $data = json_decode($string);

        return new RdKafkaMessage($data['body'], $data['properties'], $data['headers']);
    }
}
