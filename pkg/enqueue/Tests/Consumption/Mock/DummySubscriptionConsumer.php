<?php

namespace Enqueue\Tests\Consumption\Mock;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrSubscriptionConsumer;

class DummySubscriptionConsumer implements PsrSubscriptionConsumer
{
    private $subscriptions = [];

    private $messages = [];

    /**
     * @param float|int $timeout milliseconds 1000 is 1 second, a zero is consume endlessly
     */
    public function consume($timeout = 0)
    {
        foreach ($this->messages as list($message, $queueName)) {
            /** @var PsrMessage $message */
            /** @var string $queueName */
            if (false == call_user_func($this->subscriptions[$queueName][1], $message, $this->subscriptions[$queueName][0])) {
                return;
            }
        }
    }

    public function subscribe(PsrConsumer $consumer, callable $callback)
    {
        $this->subscriptions[$consumer->getQueue()->getQueueName()] = [$consumer, $callback];
    }

    public function unsubscribe(PsrConsumer $consumer)
    {
        unset($this->subscriptions[$consumer->getQueue()->getQueueName()]);
    }

    public function unsubscribeAll()
    {
        $this->subscriptions = [];
    }

    public function addMessage(PsrMessage $message, string $queueName)
    {
        $this->messages[] = [$message, $queueName];
    }
}
