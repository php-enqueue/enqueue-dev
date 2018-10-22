<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Exception\TimeToLiveNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Thruway\ClientSession;
use Thruway\Peer\Client;

class WampProducer implements Producer
{
    /**
     * @var WampContext
     */
    private $context;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var WampMessage
     */
    private $message;

    /**
     * @var WampDestination
     */
    private $destination;

    public function __construct(WampContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param WampDestination $destination
     * @param WampMessage $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, WampDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, WampMessage::class);

        $init = false;
        $this->message = $message;
        $this->destination = $destination;

        if (null === $this->client) {
            $init = true;

            $this->client = $this->context->getClient();
            $this->client->setAttemptRetry(true);
            $this->client->on('open', function (ClientSession $session) {
                $this->session = $session;

                $this->doSendMessageIfPossible();
            });

            $this->client->on('close', function () {
                if ($this->session === $this->client->getSession()) {
                    $this->session = null;
                }
            });

            $this->client->on('error', function () {
                if ($this->session === $this->client->getSession()) {
                    $this->session = null;
                }
            });

            $this->client->on('do-send', function (WampDestination $destination, WampMessage $message) {

                $onFinish = function () {
                    $this->client->emit('do-stop');
                };

                $this->session->publish($destination->getTopicName(), [json_encode($message->jsonSerialize())], [], ['acknowledge' => true])
                    ->then($onFinish, $onFinish);
            });

            $this->client->on('do-stop', function () {
                $this->client->getLoop()->stop();
            });
        }

        $this->client->getLoop()->futureTick(function () {
            $this->doSendMessageIfPossible();
        });

        if ($init) {
            $this->client->start(false);
        }

        $this->client->getLoop()->run();
    }

    private function doSendMessageIfPossible()
    {
        if (null === $this->session) {
            return;
        }

        if (null === $this->message) {
            return;
        }

        $message = $this->message;
        $destination = $this->destination;

        $this->message = null;
        $this->destination = null;

        $this->client->emit('do-send', [$destination, $message]);
    }

    /**
     * {@inheritdoc}
     *
     * @return WampProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return WampProducer
     */
    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return WampProducer
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        if (null === $timeToLive) {
            return $this;
        }

        throw TimeToLiveNotSupportedException::providerDoestNotSupportIt();
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
