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
     * Note: If you set prefix_queue to true then the queue is used as is and therefor the driver is not used to prepare a transport queue name.
     * It is possible to pass other options, they could be accessible on a route instance through options.
     *
     * @return string|array
     */
    public static function getSubscribedTopics();
}
