<?php

namespace Enqueue\Client;

interface TopicSubscriberInterface
{
    /**
     * The result maybe either:.
     *
     * 'aTopicName'
     *
     * or
     *
     * ['aTopicName', 'anotherTopicName']
     *
     * or
     *
     * ['aTopicName' => [
     *     'processor' => 'processor',
     *     'queue' => 'a_client_queue_name',
     * ]]
     *
     * Note: If you set queueNameHardcoded to true then the queueName is used as is and therefor the driver is not used to create a transport queue name.
     *
     * @return string|array
     */
    public static function getSubscribedTopics();
}
