<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Stomp\Client;
use Stomp\Transport\Frame;

class StompConsumer implements Consumer
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

    public function __construct(BufferedStompClient $stomp, StompDestination $queue)
    {
        $this->stomp = $stomp;
        $this->queue = $queue;
        $this->isSubscribed = false;
        $this->ackMode = self::ACK_CLIENT_INDIVIDUAL;
        $this->prefetchCount = 1;
        $this->subscriptionId = StompDestination::TYPE_TEMP_QUEUE == $queue->getType() ?
            $queue->getQueueName() :
            uniqid('', true)
        ;
    }

    public function setAckMode(string $mode): void
    {
        if (false === in_array($mode, [self::ACK_AUTO, self::ACK_CLIENT, self::ACK_CLIENT_INDIVIDUAL], true)) {
            throw new \LogicException(sprintf('Ack mode is not valid: "%s"', $mode));
        }

        $this->ackMode = $mode;
    }

    public function getAckMode(): string
    {
        return $this->ackMode;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function setPrefetchCount(int $prefetchCount): void
    {
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * @return StompDestination
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function receive(int $timeout = 0): ?Message
    {
        $this->subscribe();

        if (0 === $timeout) {
            while (true) {
                if ($message = $this->stomp->readMessageFrame($this->subscriptionId, 100)) {
                    return $this->convertMessage($message);
                }
            }
        } else {
            if ($message = $this->stomp->readMessageFrame($this->subscriptionId, $timeout)) {
                return $this->convertMessage($message);
            }
        }

        return null;
    }

    public function receiveNoWait(): ?Message
    {
        $this->subscribe();

        if ($message = $this->stomp->readMessageFrame($this->subscriptionId, 0)) {
            return $this->convertMessage($message);
        }

        return null;
    }

    /**
     * @param StompMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $this->stomp->sendFrame(
            $this->stomp->getProtocol()->getAckFrame($message->getFrame())
        );
    }

    /**
     * @param StompMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $nackFrame = $this->stomp->getProtocol()->getNackFrame($message->getFrame());

        // rabbitmq STOMP protocol extension
        $nackFrame->addHeaders([
            'requeue' => $requeue ? 'true' : 'false',
        ]);

        $this->stomp->sendFrame($nackFrame);
    }

    private function subscribe(): void
    {
        if (StompDestination::TYPE_TEMP_QUEUE == $this->queue->getType()) {
            $this->isSubscribed = true;

            return;
        }

        if (false == $this->isSubscribed) {
            $this->isSubscribed = true;

            $frame = $this->stomp->getProtocol()->getSubscribeFrame(
                $this->queue->getQueueName(),
                $this->subscriptionId,
                $this->ackMode
            );

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

    private function convertMessage(Frame $frame): StompMessage
    {
        if ('MESSAGE' !== $frame->getCommand()) {
            throw new \LogicException(sprintf('Frame is not MESSAGE frame but: "%s"', $frame->getCommand()));
        }

        list($headers, $properties) = StompHeadersEncoder::decode($frame->getHeaders());

        $redelivered = isset($headers['redelivered']) && 'true' === $headers['redelivered'];

        unset(
            $headers['redelivered'],
            $headers['destination'],
            $headers['message-id'],
            $headers['ack'],
            $headers['receipt'],
            $headers['subscription'],
            $headers['content-length']
        );

        $message = new StompMessage((string) $frame->getBody(), $properties, $headers);
        $message->setRedelivered($redelivered);
        $message->setFrame($frame);

        return $message;
    }
}
