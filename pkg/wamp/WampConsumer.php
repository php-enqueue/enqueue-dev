<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use React\EventLoop\TimerInterface;
use Thruway\ClientSession;
use Thruway\Peer\Client;

class WampConsumer implements Consumer
{
    /**
     * @var WampContext
     */
    private $context;

    /**
     * @var WampDestination
     */
    private $queue;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var WampMessage
     */
    private $message;

    /**
     * @var TimerInterface
     */
    private $timer;

    public function __construct(WampContext $context, WampDestination $destination)
    {
        $this->context = $context;
        $this->queue = $destination;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function receive(int $timeout = 0): ?Message
    {
        $init = false;
        $this->timer = null;
        $this->message = null;

        if (null === $this->client) {
            $init = true;

            $this->client = $this->context->getNewClient();
            $this->client->setAttemptRetry(true);
            $this->client->on('open', function (ClientSession $session) {

                $session->subscribe($this->queue->getQueueName(), function ($args) {
                    $this->message = $this->context->getSerializer()->toMessage($args[0]);

                    $this->client->emit('do-stop');
                });
            });

            $this->client->on('do-stop', function () {
                if ($this->timer) {
                    $this->client->getLoop()->cancelTimer($this->timer);
                }

                $this->client->getLoop()->stop();
            });
        }

        if ($timeout > 0) {
            $timeout = $timeout / 1000;
            $timeout = $timeout >= 0.1 ? $timeout : 0.1;

            $this->timer = $this->client->getLoop()->addTimer($timeout, function () {
                $this->client->emit('do-stop');
            });
        }

        if ($init) {
            $this->client->start(false);
        }

        $this->client->getLoop()->run();

        $message = $this->message;

        $this->timer = null;
        $this->message = null;

        return $message;
    }

    public function receiveNoWait(): ?Message
    {
        return $this->receive(100);
    }

    /**
     * {@inheritdoc}
     *
     * @param WampMessage $message
     */
    public function acknowledge(Message $message): void
    {
        // do nothing. wamp transport always works in auto ack mode
    }

    /**
     * {@inheritdoc}
     *
     * @param WampMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, WampMessage::class);

        // do nothing on reject. wamp transport always works in auto ack mode

        if ($requeue) {
            $this->context->createProducer()->send($this->queue, $message);
        }
    }
}
