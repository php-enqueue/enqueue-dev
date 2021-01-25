<?php

namespace Enqueue\Tests\Client\Driver;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;

trait GenericDriverTestsTrait
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $driver = $this->createDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->assertInstanceOf(DriverInterface::class, $driver);
    }

    public function testShouldReturnContextSetInConstructor()
    {
        $context = $this->createContextMock();

        $driver = $this->createDriver($context, $this->createDummyConfig(), new RouteCollection([]));

        $this->assertSame($context, $driver->getContext());
    }

    public function testShouldReturnConfigObjectSetInConstructor()
    {
        $config = $this->createDummyConfig();

        $driver = $this->createDriver($this->createContextMock(), $config, new RouteCollection([]));

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldReturnRouteCollectionSetInConstructor()
    {
        $routeCollection = new RouteCollection([]);

        /** @var DriverInterface $driver */
        $driver = $this->createDriver($this->createContextMock(), $this->createDummyConfig(), $routeCollection);

        $this->assertSame($routeCollection, $driver->getRouteCollection());
    }

    public function testShouldCreateAndReturnQueueInstanceWithPrefixAndAppName()
    {
        $expectedQueue = $this->createQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getPrefixAppFooQueueTransportName())
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            'aPrefix',
            '.',
            'anAppName',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            [],
            []
        );

        $driver = $this->createDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithPrefixWithoutAppName()
    {
        $expectedQueue = $this->createQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getPrefixFooQueueTransportName())
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            'aPrefix',
            '.',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            [],
            []
        );

        $driver = $this->createDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithAppNameAndWithoutPrefix()
    {
        $expectedQueue = $this->createQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getAppFooQueueTransportName())
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            '',
            '.',
            'anAppName',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            [],
            []
        );

        $driver = $this->createDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithoutPrefixAndAppName()
    {
        $expectedQueue = $this->createQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('afooqueue')
            ->willReturn($expectedQueue)
        ;

        $config = new Config(
            '',
            '.',
            '',
            'aRouterTopicName',
            'aRouterQueueName',
            'aDefaultQueue',
            'aRouterProcessor',
            [],
            []
        );

        $driver = $this->createDriver($context, $config, new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = $this->createQueue('aName');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getPrefixFooQueueTransportName())
            ->willReturn($expectedQueue)
        ;

        $driver = $this->createDriver($context, $this->createDummyConfig(), new RouteCollection([]));

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateClientMessageFromTransportOne()
    {
        $transportMessage = $this->createMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperty('pkey', 'pval');
        $transportMessage->setProperty(Config::CONTENT_TYPE, 'theContentType');
        $transportMessage->setProperty(Config::EXPIRE, '22');
        $transportMessage->setProperty(Config::PRIORITY, MessagePriority::HIGH);
        $transportMessage->setProperty('enqueue.delay', '44');
        $transportMessage->setMessageId('theMessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = $this->createDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertClientMessage($clientMessage);
    }

    public function testShouldCreateTransportMessageFromClientOne()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['pkey' => 'pval']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setDelay(345);
        $clientMessage->setPriority(MessagePriority::HIGH);
        $clientMessage->setMessageId('theMessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($this->createMessage())
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertTransportMessage($transportMessage);
    }

    public function testShouldSendMessageToRouter()
    {
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Destination $topic, InteropMessage $message) use ($transportMessage) {
                $this->assertSame(
                    $this->getRouterTransportName(),
                    $topic instanceof InteropTopic ? $topic->getTopicName() : $topic->getQueueName());
                $this->assertSame($transportMessage, $message);
            })
        ;
        $context = $this->createContextStub();
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldNotInitDeliveryDelayOnSendMessageToRouter()
    {
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $producer
            ->expects($this->never())
            ->method('setDeliveryDelay')
        ;

        $context = $this->createContextStub();
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setDelay(456);
        $message->setProperty(Config::TOPIC, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldNotInitTimeToLiveOnSendMessageToRouter()
    {
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $producer
            ->expects($this->never())
            ->method('setTimeToLive')
        ;

        $context = $this->createContextStub();
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setExpire(456);
        $message->setProperty(Config::TOPIC, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldNotInitPriorityOnSendMessageToRouter()
    {
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $producer
            ->expects($this->never())
            ->method('setPriority')
        ;

        $context = $this->createContextStub();
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);
        $message->setProperty(Config::TOPIC, 'topic');

        $driver->sendToRouter($message);
    }

    public function testThrowIfTopicIsNotSetOnSendToRouter()
    {
        $driver = $this->createDriver(
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
        $driver = $this->createDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'aCommand');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Command must not be send to router but go directly to its processor.');

        $driver->sendToRouter($message);
    }

    public function testShouldSendMessageToRouterProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getDefaultQueueTransportName())
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

        $config = $this->createDummyConfig();

        $driver = $this->createDriver(
            $context,
            $config,
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor', [
                    'queue' => 'custom',
                ]),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, $config->getRouterProcessor());

        $driver->sendToProcessor($message);
    }

    public function testShouldSendTopicMessageToProcessorToDefaultQueue()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSendTopicMessageToProcessorToCustomQueue()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getCustomQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldInitDeliveryDelayIfDelayPropertyOnSendToProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('setDeliveryDelay')
            ->with(456000)
        ;
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $message = new Message();
        $message->setDelay(456);
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetInitTimeToLiveIfExpirePropertyOnSendToProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('setTimeToLive')
            ->with(678000)
        ;
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $message = new Message();
        $message->setExpire(678);
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetInitPriorityIfPriorityPropertyOnSendToProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('setPriority')
            ->with(3)
        ;
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no route for topic "topic" and processor "processor"');
        $driver->sendToProcessor($message);
    }

    public function testShouldSetRouterProcessorIfProcessorPropertyEmptyOnSendToProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'expectedProcessor'),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topic');

        $driver->sendToProcessor($message);

        $this->assertSame('router', $message->getProperty(Config::PROCESSOR));
    }

    public function testShouldSendCommandMessageToProcessorToDefaultQueue()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getDefaultQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'processor'),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSendCommandMessageToProcessorToCustomQueue()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getCustomQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'processor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setProperty(Config::PROCESSOR, 'processor');

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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setProperty(Config::PROCESSOR, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no route for command "command".');
        $driver->sendToProcessor($message);
    }

    public function testShouldOverwriteProcessorPropertySetByOneFromCommandRouteOnSendToProcessor()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

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
            ->with($this->getCustomQueueTransportName())
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'expectedProcessor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setProperty(Config::PROCESSOR, 'processorShouldBeOverwritten');

        $driver->sendToProcessor($message);

        $this->assertSame('expectedProcessor', $message->getProperty(Config::PROCESSOR));
    }

    public function testShouldNotInitDeliveryDelayOnSendMessageToProcessorIfPropertyNull()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('setDeliveryDelay')
        ;
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'expectedProcessor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setDelay(null);

        $driver->sendToProcessor($message);
    }

    public function testShouldNotInitPriorityOnSendMessageToProcessorIfPropertyNull()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('setPriority')
        ;
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'expectedProcessor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setPriority(null);

        $driver->sendToProcessor($message);
    }

    public function testShouldNotInitTimeToLiveOnSendMessageToProcessorIfPropertyNull()
    {
        $queue = $this->createQueue('');
        $transportMessage = $this->createMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('setTimeToLive')
        ;
        $producer
            ->expects($this->once())
            ->method('send')
        ;
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
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

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('command', Route::COMMAND, 'expectedProcessor', ['queue' => 'custom']),
            ])
        );

        $message = new Message();
        $message->setProperty(Config::COMMAND, 'command');
        $message->setExpire(null);

        $driver->sendToProcessor($message);
    }

    public function testThrowIfNeitherTopicNorCommandAreSentOnSendToProcessor()
    {
        $driver = $this->createDriver(
            $this->createContextMock(),
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PROCESSOR, 'processor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either topic or command parameter must be set.');
        $driver->sendToProcessor($message);
    }

    abstract protected function createDriver(...$args): DriverInterface;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    abstract protected function createContextMock(): Context;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    abstract protected function createProducerMock(): InteropProducer;

    abstract protected function createQueue(string $name): InteropQueue;

    abstract protected function createTopic(string $name): InteropTopic;

    abstract protected function createMessage(): InteropMessage;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createContextStub(): Context
    {
        $context = $this->createContextMock();

        $context
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $name) {
                return $this->createQueue($name);
            })
        ;

        $context
            ->expects($this->any())
            ->method('createTopic')
            ->willReturnCallback(function (string $name) {
                return $this->createTopic($name);
            })
        ;

        return $context;
    }

    protected function assertTransportMessage(InteropMessage $transportMessage): void
    {
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
            Config::CONTENT_TYPE => 'ContentType',
            Config::PRIORITY => MessagePriority::HIGH,
            Config::EXPIRE => 123,
            Config::DELAY => 345,
        ], $transportMessage->getProperties());
        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    protected function assertClientMessage(Message $clientMessage): void
    {
        $this->assertSame('body', $clientMessage->getBody());
        Assert::assertArraySubset([
            'hkey' => 'hval',
        ], $clientMessage->getHeaders());
        Assert::assertArraySubset([
            'pkey' => 'pval',
            Config::CONTENT_TYPE => 'theContentType',
            Config::EXPIRE => '22',
            Config::PRIORITY => MessagePriority::HIGH,
            Config::DELAY => '44',
        ], $clientMessage->getProperties());
        $this->assertSame('theMessageId', $clientMessage->getMessageId());
        $this->assertSame(22, $clientMessage->getExpire());
        $this->assertSame(44, $clientMessage->getDelay());
        $this->assertSame(MessagePriority::HIGH, $clientMessage->getPriority());
        $this->assertSame('theContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    protected function createDummyConfig(): Config
    {
        return Config::create('aPrefix');
    }

    protected function getDefaultQueueTransportName(): string
    {
        return 'aprefix.default';
    }

    protected function getCustomQueueTransportName(): string
    {
        return 'aprefix.custom';
    }

    protected function getRouterTransportName(): string
    {
        return 'aprefix.default';
    }

    protected function getPrefixAppFooQueueTransportName(): string
    {
        return 'aprefix.anappname.afooqueue';
    }

    protected function getPrefixFooQueueTransportName(): string
    {
        return 'aprefix.afooqueue';
    }

    protected function getAppFooQueueTransportName(): string
    {
        return 'anappname.afooqueue';
    }
}
