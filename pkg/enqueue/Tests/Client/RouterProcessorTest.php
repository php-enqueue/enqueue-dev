<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;

class RouterProcessorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorInterface()
    {
        $this->assertClassImplements(PsrProcessor::class, RouterProcessor::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(RouterProcessor::class);
    }

    public function testCouldBeConstructedWithDriverAndRouteCollection()
    {
        $driver = $this->createDriverMock();
        $routeCollection = new RouteCollection([]);

        $processor = new RouterProcessor($driver, $routeCollection);

        $this->assertAttributeSame($driver, 'driver', $processor);
        $this->assertAttributeSame($routeCollection, 'routeCollection', $processor);
    }

    public function testShouldRejectIfNeitherTopicNorCommandParameterIsSet()
    {
        $router = new RouterProcessor($this->createDriverMock(), new RouteCollection([]));

        $result = $router->process(new NullMessage(), new NullContext());

        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('Got message without required parameters. Either "enqueue.topic_name" or "enqueue.command_name" property should be set', $result->getReason());
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

        $processor = new RouterProcessor($driver, new RouteCollection([
            new Route('theTopicName', Route::TOPIC, 'aFooProcessor', ['queue' => 'aQueueName']),
        ]));

        $result = $processor->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertEquals('Routed to "1" event subscribers', $result->getReason());

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
            'enqueue.command_name' => 'theCommandName',
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

        $processor = new RouterProcessor($driver, new RouteCollection([
            new Route('theCommandName', Route::COMMAND, 'aFooProcessor', ['queue' => 'aQueueName']),
        ]));

        $result = $processor->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertEquals('Routed to the command processor', $result->getReason());

        $this->assertEquals([
            'aProp' => 'aPropVal',
            'enqueue.processor_name' => 'aFooProcessor',
            'enqueue.command_name' => 'theCommandName',
            'enqueue.processor_queue_name' => 'aQueueName',
        ], $routedMessage->getProperties());
    }

    public function testThrowIfNoRouteForGivenCommand()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties([
            'aProp' => 'aPropVal',
            'enqueue.command_name' => 'theCommandName',
        ]);

        $clientMessage = new Message();

        $routedMessage = null;

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $processor = new RouterProcessor($driver, new RouteCollection([]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command "theCommandName" processor not found');
        $processor->process($message, new NullContext());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
