<?php

namespace Enqueue\Gps;

use Google\Cloud\Core\Exception\ServiceException;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\Subscription;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class GpsConsumer implements PsrConsumer
{
    /**
     * @var GpsContext
     */
    private $context;

    /**
     * @var GpsQueue
     */
    private $queue;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @param GpsContext $context
     * @param GpsQueue   $queue
     */
    public function __construct(GpsContext $context, GpsQueue $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function receive($timeout = 0)
    {
        if ($timeout === 0) {
            while (true) {
                if ($message = $this->receiveMessage($timeout)) {
                    return $message;
                }
            }
        } else {
            return $this->receiveMessage($timeout);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        $messages = $this->getSubscription()->pull([
            'maxMessages' => 1,
            'returnImmediately' => true,
        ]);

        if ($messages) {
            return $this->convertMessage(current($messages));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(PsrMessage $message)
    {
        if (false == $message->getNativeMessage()) {
            throw new \LogicException('Native google pub/sub message required but it is empty');
        }

        $this->getSubscription()->acknowledge($message->getNativeMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        if (false == $message->getNativeMessage()) {
            throw new \LogicException('Native google pub/sub message required but it is empty');
        }

        $this->getSubscription()->acknowledge($message->getNativeMessage());
    }

    /**
     * @return Subscription
     */
    private function getSubscription()
    {
        if (null === $this->subscription) {
            $this->subscription = $this->context->getClient()->subscription($this->queue->getQueueName());
        }

        return $this->subscription;
    }

    /**
     * @param Message $message
     *
     * @return GpsMessage
     */
    private function convertMessage(Message $message)
    {
        $gpsMessage = GpsMessage::jsonUnserialize($message->data());
        $gpsMessage->setNativeMessage($message);

        return $gpsMessage;
    }

    /**
     * @param int $timeout
     *
     * @return GpsMessage|null
     */
    private function receiveMessage($timeout)
    {
        $timeout /= 1000;

        try {
            $messages = $this->getSubscription()->pull([
                'maxMessages' => 1,
                'requestTimeout' => $timeout,
            ]);

            if ($messages) {
                return $this->convertMessage(current($messages));
            }
        } catch (ServiceException $e) {} // timeout
    }
}
