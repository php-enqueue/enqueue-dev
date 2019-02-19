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
     *   'command' => 'aSubscribedCommand',
     *   'processor' => 'aProcessorName',
     *   'queue' => 'a_client_queue_name',
     *   'prefix_queue' => true,
     *   'exclusive' => true,
     * ]
     *
     * or
     *
     * [
     *   [
     *     'command' => 'aSubscribedCommand',
     *     'processor' => 'aProcessorName',
     *     'queue' => 'a_client_queue_name',
     *     'prefix_queue' => true,
     *     'exclusive' => true,
     *   ],
     *   [
     *     'command' => 'aSubscribedCommand',
     *     'processor' => 'aProcessorName',
     *     'queue' => 'a_client_queue_name',
     *     'prefix_queue' => true,
     *     'exclusive' => true,
     *   ]
     * ]
     *
     * queue, processor, prefix_queue, and exclusive are optional.
     * It is possible to pass other options, they could be accessible on a route instance through options.
     *
     * Note: If you set "prefix_queue" to true then the "queue" is used as is and therefor the driver is not used to create a transport queue name.
     *
     * @return string|array
     */
    public static function getSubscribedCommand();
}
