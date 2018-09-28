<?php

namespace Enqueue\Tests\Consumption\Mock;

use Interop\Queue\Consumer;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\PsrSubscriptionConsumer;

class DummySubscriptionConsumer implements PsrSubscriptionConsumer
{
    private $subscriptions = [];

    private $messages = [];

    /**
     * @param float|int $timeout milliseconds 1000 is 1 second, a zero is consume endlessly
     */
    public function consume(int $timeout = 0): void
    {
        foreach ($this->messages as list($message, $queueName)) {
            /** @var InteropMessage $message */
            /** @var string $queueName */
            if (false == call_user_func($this->subscriptions[$queueName][1], $message, $this->subscriptions[$queueName][0])) {
                return;
            }
        }
    }

    public function subscribe(Consumer $consumer, callable $callback): void
    {
        $this->subscriptions[$consumer->getQueue()->getQueueName()] = [$consumer, $callback];
    }

    public function unsubscribe(Consumer $consumer): void
    {
        unset($this->subscriptions[$consumer->getQueue()->getQueueName()]);
    }

    public function unsubscribeAll(): void
    {
        $this->subscriptions = [];
    }

    public function addMessage(InteropMessage $message, string $queueName): void
    {
        $this->messages[] = [$message, $queueName];
    }
}
