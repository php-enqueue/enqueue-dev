<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\RouterProcessor;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use PHPUnit\Framework\TestCase;

class RouterProcessorTest extends TestCase
{
    public function testCouldBeConstructedWithDriverAsFirstArgument()
    {
        new RouterProcessor($this->createDriverMock());
    }

    public function testCouldBeConstructedWithSessionAndRoutes()
    {
        $routes = [
            'aTopicName' => [['aProcessorName', 'aQueueName']],
            'anotherTopicName' => [['aProcessorName', 'aQueueName']],
        ];

        $router = new RouterProcessor($this->createDriverMock(), $routes);

        $this->assertAttributeEquals($routes, 'eventRoutes', $router);
    }

    public function testShouldRejectIfTopicNameParameterIsNotSet()
    {
        $router = new RouterProcessor($this->createDriverMock());

        $result = $router->process(new NullMessage(), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('Got message without required parameter: "enqueue.topic_name"', $result->getReason());
    }

    public function testShouldRouteOriginalMessageToEventRecipient()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::PARAMETER_TOPIC_NAME => 'theTopicName']);

        $clientMessage = new Message();

        $routedMessage = null;

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with($this->identicalTo($clientMessage))
        ;
        $driver
            ->expects($this->once())
            ->method('createClientMessage')
            ->willReturnCallback(function (NullMessage $message) use (&$routedMessage, $clientMessage) {
                $routedMessage = $message;

                return $clientMessage;
            })
        ;

        $routes = [
            'theTopicName' => [['aFooProcessor', 'aQueueName']],
        ];

        $router = new RouterProcessor($driver, $routes);

        $result = $router->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result);
        $this->assertEquals([
            'aProp' => 'aPropVal',
            'enqueue.topic_name' => 'theTopicName',
            'enqueue.processor_name' => 'aFooProcessor',
            'enqueue.processor_queue_name' => 'aQueueName',
        ], $routedMessage->getProperties());
    }

    public function testShouldRouteOriginalMessageToCommandRecipient()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties([
            'aProp' => 'aPropVal',
            Config::PARAMETER_TOPIC_NAME => Config::COMMAND_TOPIC,
            Config::PARAMETER_COMMAND_NAME => 'theCommandName',
        ]);

        $clientMessage = new Message();

        $routedMessage = null;

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with($this->identicalTo($clientMessage))
        ;
        $driver
            ->expects($this->once())
            ->method('createClientMessage')
            ->willReturnCallback(function (NullMessage $message) use (&$routedMessage, $clientMessage) {
                $routedMessage = $message;

                return $clientMessage;
            })
        ;

        $routes = [
            'theCommandName' => 'aQueueName',
        ];

        $router = new RouterProcessor($driver, [], $routes);

        $result = $router->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result);
        $this->assertEquals([
            'aProp' => 'aPropVal',
            'enqueue.topic_name' => Config::COMMAND_TOPIC,
            'enqueue.processor_name' => 'theCommandName',
            'enqueue.command_name' => 'theCommandName',
            'enqueue.processor_queue_name' => 'aQueueName',
        ], $routedMessage->getProperties());
    }

    public function testShouldRejectCommandMessageIfCommandNamePropertyMissing()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties([
            'aProp' => 'aPropVal',
            Config::PARAMETER_TOPIC_NAME => Config::COMMAND_TOPIC,
        ]);

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;
        $driver
            ->expects($this->never())
            ->method('createClientMessage')
        ;

        $routes = [
            'theCommandName' => 'aQueueName',
        ];

        $router = new RouterProcessor($driver, [], $routes);

        $result = $router->process($message, new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('Got message without required parameter: "enqueue.command_name"', $result->getReason());
    }

    public function testShouldAddEventRoute()
    {
        $router = new RouterProcessor($this->createDriverMock(), []);

        $this->assertAttributeSame([], 'eventRoutes', $router);

        $router->add('theTopicName', 'theQueueName', 'aProcessorName');

        $this->assertAttributeSame([
            'theTopicName' => [
                ['aProcessorName', 'theQueueName'],
            ],
        ], 'eventRoutes', $router);

        $this->assertAttributeSame([], 'commandRoutes', $router);
    }

    public function testShouldAddCommandRoute()
    {
        $router = new RouterProcessor($this->createDriverMock(), []);

        $this->assertAttributeSame([], 'eventRoutes', $router);

        $router->add(Config::COMMAND_TOPIC, 'theQueueName', 'aProcessorName');

        $this->assertAttributeSame(['aProcessorName' => 'theQueueName'], 'commandRoutes', $router);
        $this->assertAttributeSame([], 'eventRoutes', $router);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
