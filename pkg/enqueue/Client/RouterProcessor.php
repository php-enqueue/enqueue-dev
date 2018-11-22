<?php

namespace Enqueue\Client;

use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

final class RouterProcessor implements Processor
{
    /**
     * compatibility with 0.8x.
     */
    private const COMMAND_TOPIC_08X = '__command__';

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function process(InteropMessage $message, Context $context): Result
    {
        // compatibility with 0.8x
        if (self::COMMAND_TOPIC_08X === $message->getProperty(Config::TOPIC)) {
            $clientMessage = $this->driver->createClientMessage($message);
            $clientMessage->setProperty(Config::TOPIC, null);

            $this->driver->sendToProcessor($clientMessage);

            return Result::ack('Legacy 0.8x message routed to processor');
        }
        // compatibility with 0.8x

        if ($message->getProperty(Config::COMMAND)) {
            return Result::reject(sprintf(
                'Unexpected command "%s" got. Command must not go to the router.',
                $message->getProperty(Config::COMMAND)
            ));
        }

        $topic = $message->getProperty(Config::TOPIC);
        if (false == $topic) {
            return Result::reject(sprintf('Topic property "%s" is required but not set or empty.', Config::TOPIC));
        }

        $count = 0;
        foreach ($this->driver->getRouteCollection()->topic($topic) as $route) {
            $clientMessage = $this->driver->createClientMessage($message);
            $clientMessage->setProperty(Config::PROCESSOR, $route->getProcessor());

            $this->driver->sendToProcessor($clientMessage);

            ++$count;
        }

        return Result::ack(sprintf('Routed to "%d" event subscribers', $count));
    }
}
