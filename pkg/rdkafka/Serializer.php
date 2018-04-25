<?php

namespace Enqueue\RdKafka;

interface Serializer
{
    /**
     * @param RdKafkaMessage $message
     *
     * @return string
     */
    public function toString(RdKafkaMessage $message);

    /**
     * @param string $string
     *
     * @return RdKafkaMessage
     */
    public function toMessage($string);
}
