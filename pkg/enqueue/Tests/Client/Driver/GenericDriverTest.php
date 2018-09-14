<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use PHPUnit\Framework\TestCase;

class GenericDriverTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, GenericDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );
    }

    public function testShouldReturnPsrContextSetInConstructor()
    {
        $context = $this->createContextMock();

        $driver = new GenericDriver($context, $this->createDummyConfig(), new RouteCollection([]));

        $this->assertSame($context, $driver->getContext());
    }

    public function testShouldReturnConfigObjectSetInConstructor()
    {
        $config = $this->createDummyConfig();

        $driver = new GenericDriver($this->createContextMock(), $config, new RouteCollection([]));

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldReturnRouteCollectionSetInConstructor()
    {
        $routeCollection = new RouteCollection([]);

        $driver = new GenericDriver($this->createContextMock(), $this->createDummyConfig(), $routeCollection);

        $this->assertSame($routeCollection, $driver->getRouteCollection());
    }

    public function testShouldCreateAndReturnQueueInstanceWithPrefixAndAppName()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.anappname.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            'aPrefix',
            'anAppName',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            []
        );

        $driver = new GenericDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithPrefixWithoutAppName()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            'aPrefix',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            []
        );

        $driver = new GenericDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithAppNameAndWithoutPrefix()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('anappname.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            '',
            'anAppName',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            []
        );

        $driver = new GenericDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithoutPrefixAndAppName()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('afooqueue')
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            '',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            []
        );

        $driver = new GenericDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new NullQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new GenericDriver($context, $this->createDummyConfig(), new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new NullMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperty('pkey', 'pval');
        $transportMessage->setProperty('X-Enqueue-Content-Type', 'theContentType');
        $transportMessage->setProperty('X-Enqueue-Expire', '22');
        $transportMessage->setProperty('X-Enqueue-Priority', 'thePriority');
        $transportMessage->setProperty('X-Enqueue-Delay', '44');
        $transportMessage->setMessageId('theMessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertEquals([
            'hkey' => 'hval',
            'message_id' => 'theMessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $clientMessage->getHeaders());
        $this->assertEquals([
            'pkey' => 'pval',
            'X-Enqueue-Content-Type' => 'theContentType',
            'X-Enqueue-Expire' => '22',
            'X-Enqueue-Priority' => 'thePriority',
            'X-Enqueue-Delay' => '44',
        ], $clientMessage->getProperties());
        $this->assertSame('theMessageId', $clientMessage->getMessageId());
        $this->assertSame(22, $clientMessage->getExpire());
        $this->assertSame(44, $clientMessage->getDelay());
        $this->assertSame('thePriority', $clientMessage->getPriority());
        $this->assertSame('theContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['pkey' => 'pval']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setDelay(345);
        $clientMessage->setPriority('thePriority');
        $clientMessage->setMessageId('theMessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new NullMessage())
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(NullMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertEquals([
            'hkey' => 'hval',
            'message_id' => 'theMessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $transportMessage->getHeaders());
        $this->assertEquals([
            'pkey' => 'pval',
            'X-Enqueue-Content-Type' => 'ContentType',
            'X-Enqueue-Priority' => 'thePriority',
            'X-Enqueue-Expire' => 123,
            'X-Enqueue-Delay' => 345,
        ], $transportMessage->getProperties());
        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new NullTopic('');
        $transportMessage = new NullMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->willReturn($topic)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testThrowIfTopicIsNotSetOnSendToRouter()
    {
        $driver = new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testThrowIfCommandSetOnSendToRouter()
    {
        $driver = new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'aCommand');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Command must not be send to router but go directly to its processor.');

        $driver->sendToRouter($message);
    }

    public function testShouldSendTopicMessageToProcessorToDefaultQueue()
    {
        $queue = new NullQueue('');
        $transportMessage = new NullMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSendTopicMessageToProcessorToCustomQueue()
    {
        $queue = new NullQueue('');
        $transportMessage = new NullMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('custom')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testThrowIfNoRouteFoundForTopicMessageOnSendToProcessor()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no route for topic "topic" and processor "processor"');
        $driver->sendToProcessor($message);
    }

    public function testShouldSendCommandMessageToProcessorToDefaultQueue()
    {
        $queue = new NullQueue('');
        $transportMessage = new NullMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('default')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'processor'),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'command');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSendCommandMessageToProcessorToCustomQueue()
    {
        $queue = new NullQueue('');
        $transportMessage = new NullMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('custom')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'processor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'command');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testThrowIfNoRouteFoundForCommandMessageOnSendToProcessor()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'command');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no route for command "command" and processor "processor"');
        $driver->sendToProcessor($message);
    }

    public function testThrowIfRouteProcessorDoesNotMatchMessageOneOnSendToProcessor()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->never())
            ->method('createProducer')
        ;
        $context
            ->expects($this->never())
            ->method('createMessage')
        ;

        $driver = new GenericDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'anotherProcessor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'command');
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command "command" route was found but processors do not match. Given "processor", route "anotherProcessor"');
        $driver->sendToProcessor($message);
    }

    public function testThrowIfProcessorIsNotSetOnSendToProcessor()
    {
        $driver = new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testThrowIfNeitherTopicNorCommandAreSentOnSendToProcessor()
    {
        $driver = new GenericDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either topic or command parameter must be set.');
        $driver->sendToProcessor($message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextMock(): PsrContext
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createProducerMock(): PsrProducer
    {
        return $this->createMock(PsrProducer::class);
    }

    private function createDummyConfig(): Config
    {
        return Config::create('aPrefix');
    }
}
