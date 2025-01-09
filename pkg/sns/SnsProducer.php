<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Exception\TimeToLiveNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class SnsProducer implements Producer
{
    /**
     * @var int|null
     */
    private $deliveryDelay;

    /**
     * @var SnsContext
     */
    private $context;

    public function __construct(SnsContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param SnsDestination $destination
     * @param SnsMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SnsDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, SnsMessage::class);

        $body = $message->getBody();
        if (empty($body)) {
            throw new InvalidMessageException('The message body must be a non-empty string.');
        }

        $topicArn = $this->context->getTopicArn($destination);

        $arguments = [
            'Message' => $message->getBody(),
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => json_encode([$message->getHeaders(), $message->getProperties()]),
                ],
            ],
            'TopicArn' => $topicArn,
        ];

        if (null !== $message->getMessageAttributes()) {
            $arguments['MessageAttributes'] = array_merge(
                $arguments['MessageAttributes'],
                $message->getMessageAttributes()
            );
        }

        if (null !== ($structure = $message->getMessageStructure())) {
            $arguments['MessageStructure'] = $structure;
        }
        if (null !== ($phone = $message->getPhoneNumber())) {
            $arguments['PhoneNumber'] = $phone;
        }
        if (null !== ($subject = $message->getSubject())) {
            $arguments['Subject'] = $subject;
        }
        if (null !== ($targetArn = $message->getTargetArn())) {
            $arguments['TargetArn'] = $targetArn;
        }

        if ($messageGroupId = $message->getMessageGroupId()) {
            $arguments['MessageGroupId'] = $messageGroupId;
        }

        if ($messageDeduplicationId = $message->getMessageDeduplicationId()) {
            $arguments['MessageDeduplicationId'] = $messageDeduplicationId;
        }

        $result = $this->context->getSnsClient()->publish($arguments);

        if (false == $result->hasKey('MessageId')) {
            throw new \RuntimeException('Message was not sent');
        }

        $message->setSnsMessageId((string) $result->get('MessageId'));
    }

    /**
     * @throws DeliveryDelayNotSupportedException
     *
     * @return SnsProducer
     */
    public function setDeliveryDelay(?int $deliveryDelay = null): Producer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    /**
     * @throws PriorityNotSupportedException
     *
     * @return SnsProducer
     */
    public function setPriority(?int $priority = null): Producer
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
     * @throws TimeToLiveNotSupportedException
     *
     * @return SnsProducer
     */
    public function setTimeToLive(?int $timeToLive = null): Producer
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
