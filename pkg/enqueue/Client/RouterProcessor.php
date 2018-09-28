<?php

namespace Enqueue\Client;

use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

final class RouterProcessor implements Processor
{
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
        if ($message->getProperty(Config::PARAMETER_COMMAND_NAME)) {
            return Result::reject(sprintf(
                'Unexpected command "%s" got. Command must not go to the router.',
                $message->getProperty(Config::PARAMETER_COMMAND_NAME)
            ));
        }

        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if (false == $topic) {
            return Result::reject(sprintf('Topic property "%s" is required but not set or empty.', Config::PARAMETER_TOPIC_NAME));
        }

        $count = 0;
        foreach ($this->driver->getRouteCollection()->topic($topic) as $route) {
            $clientMessage = $this->driver->createClientMessage($message);
            $clientMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $route->getProcessor());

            $this->driver->sendToProcessor($clientMessage);

            ++$count;
        }

        return Result::ack(sprintf('Routed to "%d" event subscribers', $count));
    }
}
