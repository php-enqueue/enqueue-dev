<?php

namespace Enqueue\Client;

interface CommandSubscriberInterface
{
    /**
     * The result maybe either:.
     *
     * 'aCommandName'
     *
     * or
     *
     * [
     *   'processorName' => 'aCommandName',
     *   'queueName' => 'a_client_queue_name',
     *   'queueNameHardcoded' => true,
     *   'exclusive' => true,
     * ]
     *
     * queueName, exclusive and queueNameHardcoded are optional.
     *
     * Note: If you set queueNameHardcoded to true then the queueName is used as is and therefor the driver is not used to create a transport queue name.
     *
     * @return string|array
     */
    public static function getSubscribedCommand();
}
