<?php

namespace Enqueue\Client;

class MessagePriority
{
    public const VERY_LOW = 'enqueue.message_queue.client.very_low_message_priority';
    public const LOW = 'enqueue.message_queue.client.low_message_priority';
    public const NORMAL = 'enqueue.message_queue.client.normal_message_priority';
    public const HIGH = 'enqueue.message_queue.client.high_message_priority';
    public const VERY_HIGH = 'enqueue.message_queue.client.very_high_message_priority';
}
