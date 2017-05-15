<?php

namespace Enqueue\Client;

interface TopicSubscriberInterface
{
    /**
     * The result maybe either:.
     *
     * ['aTopicName']
     *
     * or
     *
     * ['aTopicName' => [
     *     'processorName' => 'processor',
     *     'queueName' => 'a_client_queue_name',
     *     'queueNameHardcoded' => true,
     *   ]]
     *
     * processorName, queueName and queueNameHardcoded are optional.
     *
     * Note: If you set queueNameHardcoded to true then the queueName is used as is and therefor the driver is not used to create a transport queue name.
     *
     * @return array
     */
    public static function getSubscribedTopics();
}
