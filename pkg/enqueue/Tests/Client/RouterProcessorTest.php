<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverSendResult;
use Enqueue\Client\Message;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;

class RouterProcessorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProcessorInterface()
    {
        $this->assertClassImplements(Processor::class, RouterProcessor::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(RouterProcessor::class);
    }

    public function testCouldBeConstructedWithDriver()
    {
        $driver = $this->createDriverStub();

        $processor = new RouterProcessor($driver);

        $this->assertAttributeSame($driver, 'driver', $processor);
    }

    public function testShouldRejectIfTopicNotSet()
    {
        $router = new RouterProcessor($this->createDriverStub());

        $result = $router->process(new NullMessage(), new NullContext());

        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('Topic property "enqueue.topic" is required but not set or empty.', $result->getReason());
    }

    public function testShouldRejectIfCommandSet()
    {
        $router = new RouterProcessor($this->createDriverStub());

        $message = new NullMessage();
        $message->setProperty(Config::COMMAND, 'aCommand');

        $result = $router->process($message, new NullContext());

        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals('Unexpected command "aCommand" got. Command must not go to the router.', $result->getReason());
    }

    public function testShouldRouteOriginalMessageToAllRecipients()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::TOPIC => 'theTopicName']);

        /** @var Message[] $routedMessages */
        $routedMessages = new \ArrayObject();

        $routeCollection = new RouteCollection([
            new Route('theTopicName', Route::TOPIC, 'aFooProcessor'),
            new Route('theTopicName', Route::TOPIC, 'aBarProcessor'),
            new Route('theTopicName', Route::TOPIC, 'aBazProcessor'),
        ]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->exactly(3))
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) use ($routedMessages) {
                $routedMessages->append($message);

                return $this->createDriverSendResult();
            })
        ;
        $driver
            ->expects($this->exactly(3))
            ->method('createClientMessage')
            ->willReturnCallback(function (NullMessage $message) {
                return new Message($message->getBody(), $message->getProperties(), $message->getHeaders());
            })
        ;

        $processor = new RouterProcessor($driver);

        $result = $processor->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertEquals('Routed to "3" event subscribers', $result->getReason());

        $this->assertContainsOnly(Message::class, $routedMessages);
        $this->assertCount(3, $routedMessages);

        $this->assertSame('aFooProcessor', $routedMessages[0]->getProperty(Config::PROCESSOR));
        $this->assertSame('aBarProcessor', $routedMessages[1]->getProperty(Config::PROCESSOR));
        $this->assertSame('aBazProcessor', $routedMessages[2]->getProperty(Config::PROCESSOR));
    }

    public function testShouldDoNothingIfNoRoutes()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::TOPIC => 'theTopicName']);

        /** @var Message[] $routedMessages */
        $routedMessages = new \ArrayObject();

        $routeCollection = new RouteCollection([]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) use ($routedMessages) {
                $routedMessages->append($message);
            })
        ;
        $driver
            ->expects($this->never())
            ->method('createClientMessage')
            ->willReturnCallback(function (NullMessage $message) {
                return new Message($message->getBody(), $message->getProperties(), $message->getHeaders());
            })
        ;

        $processor = new RouterProcessor($driver);

        $result = $processor->process($message, new NullContext());

        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertEquals('Routed to "0" event subscribers', $result->getReason());

        $this->assertCount(0, $routedMessages);
    }

    public function testShouldDoNotModifyOriginalMessage()
    {
        $message = new NullMessage();
        $message->setBody('theBody');
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setProperties(['aProp' => 'aPropVal', Config::TOPIC => 'theTopicName']);

        /** @var Message[] $routedMessages */
        $routedMessages = new \ArrayObject();

        $routeCollection = new RouteCollection([
            new Route('theTopicName', Route::TOPIC, 'aFooProcessor'),
            new Route('theTopicName', Route::TOPIC, 'aBarProcessor'),
        ]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->atLeastOnce())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) use ($routedMessages) {
                $routedMessages->append($message);

                return $this->createDriverSendResult();
            });
        $driver
            ->expects($this->atLeastOnce())
            ->method('createClientMessage')
            ->willReturnCallback(function (NullMessage $message) {
                return new Message($message->getBody(), $message->getProperties(), $message->getHeaders());
            });

        $processor = new RouterProcessor($driver);

        $result = $processor->process($message, new NullContext());

        //guard
        $this->assertEquals(Result::ACK, $result->getStatus());

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal', Config::TOPIC => 'theTopicName'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverStub(RouteCollection $routeCollection = null): DriverInterface
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection)
        ;

        return $driver;
    }

    private function createDriverSendResult(): DriverSendResult
    {
        return new DriverSendResult(
            $this->createMock(Destination::class),
            $this->createMock(TransportMessage::class)
        );
    }
}
