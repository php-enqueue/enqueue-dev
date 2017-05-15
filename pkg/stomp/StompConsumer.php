<?php

namespace Enqueue\Stomp;

use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;
use Stomp\Client;
use Stomp\Transport\Frame;

class StompConsumer implements PsrConsumer
{
    const ACK_AUTO = 'auto';
    const ACK_CLIENT = 'client';
    const ACK_CLIENT_INDIVIDUAL = 'client-individual';

    /**
     * @var StompDestination
     */
    private $queue;

    /**
     * @var Client
     */
    private $stomp;

    /**
     * @var bool
     */
    private $isSubscribed;

    /**
     * @var string
     */
    private $ackMode;

    /**
     * @var int
     */
    private $prefetchCount;

    /**
     * @var string
     */
    private $subscriptionId;

    /**
     * @param BufferedStompClient $stomp
     * @param StompDestination    $queue
     */
    public function __construct(BufferedStompClient $stomp, StompDestination $queue)
    {
        $this->stomp = $stomp;
        $this->queue = $queue;
        $this->isSubscribed = false;
        $this->ackMode = self::ACK_CLIENT_INDIVIDUAL;
        $this->prefetchCount = 1;
        $this->subscriptionId = $queue->getType() == StompDestination::TYPE_TEMP_QUEUE ?
            $queue->getQueueName() :
            uniqid('', true)
        ;
    }

    /**
     * @param string $mode
     */
    public function setAckMode($mode)
    {
        if (false === in_array($mode, [self::ACK_AUTO, self::ACK_CLIENT, self::ACK_CLIENT_INDIVIDUAL], true)) {
            throw new \LogicException(sprintf('Ack mode is not valid: "%s"', $mode));
        }

        $this->ackMode = $mode;
    }

    /**
     * @return string
     */
    public function getAckMode()
    {
        return $this->ackMode;
    }

    /**
     * @return int
     */
    public function getPrefetchCount()
    {
        return $this->prefetchCount;
    }

    /**
     * @param int $prefetchCount
     */
    public function setPrefetchCount($prefetchCount)
    {
        $this->prefetchCount = (int) $prefetchCount;
    }

    /**
     * {@inheritdoc}
     *
     * @return StompDestination
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
        $this->subscribe();

        if ($timeout === 0) {
            while (true) {
                if ($message = $this->stomp->readMessageFrame($this->subscriptionId, 0.1)) {
                    return $this->convertMessage($message);
                }
            }
        } else {
            if ($message = $this->stomp->readMessageFrame($this->subscriptionId, $timeout)) {
                return $this->convertMessage($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        $this->subscribe();

        if ($message = $this->stomp->readMessageFrame($this->subscriptionId, 0)) {
            return $this->convertMessage($message);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param StompMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $this->stomp->sendFrame(
            $this->stomp->getProtocol()->getAckFrame($message->getFrame())
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param StompMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $nackFrame = $this->stomp->getProtocol()->getNackFrame($message->getFrame());

        // rabbitmq STOMP protocol extension
        $nackFrame->addHeaders([
            'requeue' => $requeue ? 'true' : 'false',
        ]);

        $this->stomp->sendFrame($nackFrame);
    }

    private function subscribe()
    {
        if (StompDestination::TYPE_TEMP_QUEUE == $this->queue->getType()) {
            $this->isSubscribed = true;

            return;
        }

        if (false == $this->isSubscribed) {
            $this->isSubscribed = true;

            $frame = $this->stomp->getProtocol()
                ->getSubscribeFrame($this->queue->getQueueName(), $this->subscriptionId, $this->ackMode);

            // rabbitmq STOMP protocol extension
            $headers = $this->queue->getHeaders();
            $headers['prefetch-count'] = $this->prefetchCount;
            $headers = StompHeadersEncoder::encode($headers);

            foreach ($headers as $key => $value) {
                $frame[$key] = $value;
            }

            $this->stomp->sendFrame($frame);
        }
    }

    /**
     * @param Frame $frame
     *
     * @return StompMessage
     */
    private function convertMessage(Frame $frame)
    {
        if ('MESSAGE' !== $frame->getCommand()) {
            throw new \LogicException(sprintf('Frame is not MESSAGE frame but: "%s"', $frame->getCommand()));
        }

        list($headers, $properties) = StompHeadersEncoder::decode($frame->getHeaders());

        $redelivered = isset($headers['redelivered']) && $headers['redelivered'] === 'true';

        unset(
            $headers['redelivered'],
            $headers['destination'],
            $headers['message-id'],
            $headers['ack'],
            $headers['receipt'],
            $headers['subscription'],
            $headers['content-length']
        );

        $message = new StompMessage($frame->getBody(), $properties, $headers);
        $message->setRedelivered($redelivered);
        $message->setFrame($frame);

        return $message;
    }
}
