<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Destination;
use Interop\Queue\Producer;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageProducer implements Producer
{
    /**
     * @var QueueRestProxy
     */
    protected $client;

    public function __construct(QueueRestProxy $client)
    {
        $this->client = $client;
    }

    /**
     * @var AzureStorageDestination $destination
     * @var AzureStorageMessage $message
     * @throws InvalidDestinationException if a client uses this method with an invalid destination
     * @throws InvalidMessageException     if an invalid message is specified
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AzureStorageDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, AzureStorageMessage::class);

        $options = new CreateMessageOptions();
        $options->setTimeToLiveInSeconds(intval($this->timeToLive / 1000));
        $options->setVisibilityTimeoutInSeconds($message->getVisibilityTimeout());

        $result = $this->client->createMessage($destination->getName(), $message->getBody());
        $resultMessage = $result->getQueueMessage();

        $message->setMessageId($resultMessage->getMessageId());
        $message->setTimestamp($resultMessage->getInsertionDate()->getTimestamp());
        $message->setRedelivered($resultMessage->getDequeueCount() > 1);

        $message->setHeaders([
            'dequeueCount' => $resultMessage->getDequeueCount(),
            'expirationDate' => $resultMessage->getExpirationDate(),
            'popReceipt' => $resultMessage->getExpirationDate(),
            'nextTimeVisible' => $resultMessage->getTimeNextVisible(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        throw new DeliveryDelayNotSupportedException();
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setPriority(int $priority = null): Producer
    {
        throw new PriorityNotSupportedException();
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): ?int
    {
        return null;
    }

    /**
     * @var integer
     */
    protected $timeToLive;

    /**
     * @inheritdoc
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}