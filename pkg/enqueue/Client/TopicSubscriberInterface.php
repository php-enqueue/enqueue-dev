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
     * [
     *   [
     *     'topic' => 'aTopicName',
     *     'processor' => 'fooProcessor',
     *     'queue' => 'a_client_queue_name',
     *
     *     'aCustomOption' => 'aVal',
     *   ],
     *   [
     *     'topic' => 'anotherTopicName',
     *     'processor' => 'barProcessor',
     *     'queue' => 'a_client_queue_name',
     *
     *     'aCustomOption' => 'aVal',
     *   ],
     * ]
     *
     * Note: If you set prefix_queue to true then the queue is used as is and therefor the driver is not used to prepare a transport queue name.
     * It is possible to pass other options, they could be accessible on a route instance through options.
     *
     * @return string|array
     */
    public static function getSubscribedTopics();
}
