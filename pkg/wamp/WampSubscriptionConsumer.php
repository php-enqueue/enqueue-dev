<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;
use React\EventLoop\Timer\Timer;
use Thruway\ClientSession;
use Thruway\Peer\Client;

class WampSubscriptionConsumer implements SubscriptionConsumer
{
    /**
     * @var WampContext
     */
    private $context;

    /**
     * an item contains an array: [WampConsumer $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Timer
     */
    private $timer;

    /**
     * @param WampContext $context
     */
    public function __construct(WampContext $context)
    {
        $this->context = $context;
        $this->subscribers = [];
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        $init = false;

        if (null === $this->client) {
            $init = true;

            $this->client = $this->context->getClient();
            $this->client->setAttemptRetry(true);
            $this->client->on('open', function (ClientSession $session) {

                foreach ($this->subscribers as $queue => $subscriber) {
                    $session->subscribe($queue, function ($args) use ($subscriber, $session) {
                        $message = WampMessage::jsonUnserialize($args[0]);

                        /**
                         * @var WampConsumer $consumer
                         * @var callable $callback
                         */
                        list($consumer, $callback) = $subscriber;

                        if (false === call_user_func($callback, $message, $consumer)) {
                            $this->client->emit('do-stop');
                        }
                    });
                }
            });

            $this->client->on('do-stop', function () {
                if ($this->timer) {
                    $this->client->getLoop()->cancelTimer($this->timer);
                }

                $this->client->getLoop()->stop();
            });
        }

        if ($timeout > 0) {
            $this->timer = $this->client->getLoop()->addTimer($timeout / 1000, function () {
                $this->client->emit('do-stop');
            });
        }

        if ($init) {
            $this->client->start(false);
        }

        $this->client->getLoop()->run();
    }

    /**
     * {@inheritdoc}
     *
     * @param WampConsumer $consumer
     */
    public function subscribe(Consumer $consumer, callable $callback): void
    {
        if (false == $consumer instanceof WampConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', WampConsumer::class, get_class($consumer)));
        }

        if ($this->client) {
            throw new \LogicException('Could not subscribe after consume was called');
        }

        $queueName = $consumer->getQueue()->getQueueName();
        if (array_key_exists($queueName, $this->subscribers)) {
            if ($this->subscribers[$queueName][0] === $consumer && $this->subscribers[$queueName][1] === $callback) {
                return;
            }

            throw new \InvalidArgumentException(sprintf('There is a consumer subscribed to queue: "%s"', $queueName));
        }

        $this->subscribers[$queueName] = [$consumer, $callback];
    }

    /**
     * {@inheritdoc}
     *
     * @param WampConsumer $consumer
     */
    public function unsubscribe(Consumer $consumer): void
    {
        if (false == $consumer instanceof WampConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', WampConsumer::class, get_class($consumer)));
        }

        if ($this->client) {
            throw new \LogicException('Could not unsubscribe after consume was called');
        }

        $queueName = $consumer->getQueue()->getQueueName();

        if (false == array_key_exists($queueName, $this->subscribers)) {
            return;
        }

        if ($this->subscribers[$queueName][0] !== $consumer) {
            return;
        }

        unset($this->subscribers[$queueName]);
    }

    public function unsubscribeAll(): void
    {
        if ($this->client) {
            throw new \LogicException('Could not unsubscribe after consume was called');
        }

        $this->subscribers = [];
    }
}
