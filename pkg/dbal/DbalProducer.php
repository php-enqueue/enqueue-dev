<?php
namespace Enqueue\Dbal;

use Doctrine\DBAL\Types\Type;
use Enqueue\Psr\Exception;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;
use Enqueue\Util\JSON;

class DbalProducer implements PsrProducer
{
    /**
     * @var DbalContext
     */
    private $context;

    /**
     * @param DbalContext $context
     */
    public function __construct(DbalContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     *
     * @throws Exception
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);

        $body = $message->getBody();
        if (is_scalar($body) || is_null($body)) {
            $body = (string)$body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $dbalMessage = [
            'body' => $body,
            'headers' => JSON::encode($message->getHeaders()),
            'properties' => JSON::encode($message->getProperties()),
            'priority' => $message->getPriority(),
            'queue' => $destination->getQueueName(),
        ];

        $delay = $message->getDelay();
        if ($delay) {
            if (! is_int($delay)) {
                throw new \LogicException(sprintf(
                    'Delay must be integer but got: "%s"',
                    is_object($delay) ? get_class($delay) : gettype($delay)
                ));
            }

            if ($delay <= 0) {
                throw new \LogicException(sprintf('Delay must be positive integer but got: "%s"', $delay));
            }

            $dbalMessage['delayed_until'] = time() + $delay;
        }

        try {
            $this->context->getDbalConnection()->insert($this->context->getTableName(), $dbalMessage, [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ]);
        } catch (\Exception $e) {
            throw new Exception('The transport fails to send the message due to some internal error.', null, $e);
        }
    }
}
