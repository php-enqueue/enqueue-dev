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
        $this->redeliverMessages();

        $this->getConnection()->beginTransaction();
        try {
            $deliveryId = (string) Uuid::uuid1();

            // get top message from the queue
            $message = $this->fetchMessage([$this->queue->getQueueName()]);

            if (null == $message) {
                $this->getConnection()->commit();

                return null;
            }

            $this->markMessageAsDeliveredToConsumer($message, $deliveryId);

            $dbalMessage = $this->getMessageByDeliveryId($deliveryId);

            $this->getConnection()->commit();

            if ($dbalMessage['redelivered'] || empty($dbalMessage['time_to_live']) || $dbalMessage['time_to_live'] > time()) {
                return $this->getContext()->convertMessage($dbalMessage);
            }

            return null;
        } catch (\Exception $e) {
            $this->dbal->rollBack();

            throw $e;
        }
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

    private function deleteMessage(?string $deliveryId): void
    {
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
