<?php
namespace Enqueue\Client;

use Enqueue\Util\JSON;
use Enqueue\Util\UUID;

class MessageProducer implements MessageProducerInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if (false == $message instanceof Message) {
            $body = $message;
            $message = new Message();
            $message->setBody($body);
        }

        $this->prepareBody($message);

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topic);

        if (!$message->getMessageId()) {
            $message->setMessageId(UUID::generate());
        }

        if (!$message->getTimestamp()) {
            $message->setTimestamp(time());
        }

        if (!$message->getPriority()) {
            $message->setPriority(MessagePriority::NORMAL);
        }

        $this->driver->sendToRouter($message);
    }

    /**
     * @param Message $message
     */
    private function prepareBody(Message $message)
    {
        $body = $message->getBody();
        $contentType = $message->getContentType();

        if (is_scalar($body) || is_null($body)) {
            $contentType = $contentType ?: 'text/plain';
            $body = (string) $body;
        } elseif (is_array($body)) {
            $body = $message->getBody();
            $contentType = $message->getContentType();

            if ($contentType && $contentType !== 'application/json') {
                throw new \LogicException(sprintf('Content type "application/json" only allowed when body is array'));
            }

            // only array of scalars is allowed.
            array_walk_recursive($body, function ($value) {
                if (!is_scalar($value) && !is_null($value)) {
                    throw new \LogicException(sprintf(
                        'The message\'s body must be an array of scalars. Found not scalar in the array: %s',
                        is_object($value) ? get_class($value) : gettype($value)
                    ));
                }
            });

            $contentType = 'application/json';
            $body = JSON::encode($body);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The message\'s body must be either null, scalar or array. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $message->setContentType($contentType);
        $message->setBody($body);
    }
}
