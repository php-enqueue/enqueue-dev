<?php
namespace Enqueue\Sqs;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;

class SqsProducer implements PsrProducer
{
    /**
     * @var SqsContext
     */
    private $context;

    /**
     * @param SqsContext $context
     */
    public function __construct(SqsContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsDestination $destination
     * @param SqsMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SqsDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, SqsMessage::class);

        $body = $message->getBody();
        if (is_scalar($body) || is_null($body)) {
            $body = (string) $body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $arguments = [
            'MessageAttributes' => [
                'Headers' => [
                    'DataType' => 'String',
                    'StringValue' => json_encode([$message->getHeaders(), $message->getProperties()]),
                ],
            ],
            'MessageBody' => $body,
            'QueueUrl' => $this->context->getQueueUrl($destination),
        ];

        if ($message->getDelaySeconds()) {
            $arguments['DelaySeconds'] = $message->getDelaySeconds();
        }

        if ($message->getMessageDeduplicationId()) {
            $arguments['MessageDeduplicationId'] = $message->getMessageDeduplicationId();
        }

        if ($message->getMessageGroupId()) {
            $arguments['MessageGroupId'] = $message->getMessageGroupId();
        }

        $result = $this->context->getClient()->sendMessage($arguments);

        if (false == $result->hasKey('MessageId')) {
            throw new \RuntimeException('Message was not sent');
        }
    }
}
