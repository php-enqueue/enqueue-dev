<?php

namespace Enqueue\Client;

/**
 * @phpstan-type CommandConfig = array{
 *     command: string,
 *     processor?: string,
 *     queue?: string,
 *     prefix_queue?: bool,
 *     exclusive?: bool,
 * }
 */
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
     *
     * @phpstan-return string|CommandConfig|array<CommandConfig>
     */
    public static function getSubscribedCommand();
}
