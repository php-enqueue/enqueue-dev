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

        $this->assertAttributeEquals($routes, 'routes', $router);
    }

    public function testShouldThrowExceptionIfTopicNameParameterIsNotSet()
    {
        $router = new RouterProcessor($this->createDriverMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got message without required parameter: "enqueue.topic_name"');

        $router->process(new NullMessage(), new NullContext());
    }

    public function testShouldRouteOriginalMessageToRecipient()
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

    public function testShouldAddRoute()
    {
        $router = new RouterProcessor($this->createDriverMock(), []);

        $this->assertAttributeSame([], 'routes', $router);

        $router->add('theTopicName', 'theQueueName', 'aProcessorName');

        $this->assertAttributeSame([
            'theTopicName' => [
                ['aProcessorName', 'theQueueName'],
            ],
        ], 'routes', $router);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
