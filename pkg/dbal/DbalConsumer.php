<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Impl\ConsumerPollingTrait;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Ramsey\Uuid\Uuid;

class DbalConsumer implements Consumer
{
    use ConsumerPollingTrait,
        DbalConsumerHelperTrait;

    /**
     * @var DbalContext
     */
    private $context;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var DbalDestination
     */
    private $queue;

    /**
     * Default 20 minutes in milliseconds.
     *
     * @var int
     */
    private $redeliveryDelay;

    public function __construct(DbalContext $context, DbalDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
        $this->dbal = $this->context->getDbalConnection();

        $this->redeliveryDelay = 1200000;
    }

    public function getContext(): DbalContext
    {
        return $this->context;
    }

    public function getConnection(): Connection
    {
        return $this->dbal;
    }

    /**
     * Get interval between retry failed messages in milliseconds.
     */
    public function getRedeliveryDelay(): int
    {
        return $this->redeliveryDelay;
    }

    /**
     * Interval between retry failed messages in seconds.
     */
    public function setRedeliveryDelay(int $redeliveryDelay): self
    {
        $this->redeliveryDelay = $redeliveryDelay;

        return $this;
    }

    /**
     * @return DbalDestination
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function receiveNoWait(): ?Message
    {
        $deliveryId = (string) Uuid::uuid1();
        $redeliveryDelay = $this->getRedeliveryDelay() / 1000; // milliseconds to seconds

        $this->redeliverMessages();

        // get top message from the queue
        if ($message = $this->fetchMessage([$this->queue->getQueueName()], $deliveryId, $redeliveryDelay)) {
            if ($message['redelivered'] || empty($message['time_to_live']) || $message['time_to_live'] > time()) {
                return $this->getContext()->convertMessage($message);
            }
        }

        return null;
    }

    /**
     * @param DbalMessage $message
     */
    public function acknowledge(Message $message): void
    {
        $this->deleteMessage($message->getDeliveryId());
    }

    /**
     * @param DbalMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

        if ($requeue) {
            $this->getContext()->createProducer()->send($this->queue, $message);

            return;
        }

        $this->deleteMessage($message->getDeliveryId());
    }

    private function deleteMessage(string $deliveryId): void
    {
        if (empty($deliveryId)) {
            throw new \LogicException(sprintf('Expected record was removed but it is not. Delivery id: "%s"', $deliveryId));
        }

        $this->getConnection()->delete(
            $this->getContext()->getTableName(),
            ['delivery_id' => $deliveryId],
            ['delivery_id' => Type::STRING]
        );
    }

    private function redeliverMessages(): void
    {
        $this->getConnection()->createQueryBuilder()
            ->update($this->getContext()->getTableName())
            ->set('delivery_id', ':deliveryId')
            ->set('redelivered', ':redelivered')
            ->andWhere('delivery_id IS NOT NULL')
            ->andWhere('redeliver_after < :now')
            ->setParameter(':now', (int) time(), Type::BIGINT)
            ->setParameter('deliveryId', null, Type::STRING)
            ->setParameter('redelivered', true, Type::BOOLEAN)
            ->execute()
        ;
    }
}
