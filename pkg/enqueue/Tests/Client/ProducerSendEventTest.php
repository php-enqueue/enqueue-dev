<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverPreSend;
use Enqueue\Client\DriverSendResult;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\PostSend;
use Enqueue\Client\PreSend;
use Enqueue\Client\Producer;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Tests\Mocks\CustomPrepareBodyClientExtension;
use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;
use PHPUnit\Framework\TestCase;

class ProducerSendEventTest extends TestCase
{
    public function testShouldSendEventToRouter()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        $expectedProperties = [
            'enqueue.topic' => 'topic',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldOverwriteTopicProperty()
    {
        $message = new Message();
        $message->setProperty(Config::TOPIC, 'topicShouldBeOverwritten');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('expectedTopic', $message);

        $expectedProperties = [
            'enqueue.topic' => 'expectedTopic',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendEventWithoutPriorityByDefault()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertNull($message->getPriority());
    }

    public function testShouldSendEventWithCustomPriority()
    {
        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertSame(MessagePriority::HIGH, $message->getPriority());
    }

    public function testShouldSendEventWithGeneratedMessageId()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertNotEmpty($message->getMessageId());
    }

    public function testShouldSendEventWithCustomMessageId()
    {
        $message = new Message();
        $message->setMessageId('theCustomMessageId');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertSame('theCustomMessageId', $message->getMessageId());
    }

    public function testShouldSendEventWithGeneratedTimestamp()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertNotEmpty($message->getTimestamp());
    }

    public function testShouldSendEventWithCustomTimestamp()
    {
        $message = new Message();
        $message->setTimestamp('theCustomTimestamp');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
            ->willReturn($this->createDriverSendResult())
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);

        self::assertSame('theCustomTimestamp', $message->getTimestamp());
    }

    public function testShouldSerializeMessageToJsonByDefault()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                $this->assertSame('{"foo":"fooVal"}', $message->getBody());

                return $this->createDriverSendResult();
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', ['foo' => 'fooVal']);
    }

    public function testShouldSerializeMessageByCustomExtension()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                $this->assertSame('theEventBodySerializedByCustomExtension', $message->getBody());

                return $this->createDriverSendResult();
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock(), new ChainExtension([new CustomPrepareBodyClientExtension()]));
        $producer->sendEvent('topic', ['foo' => 'fooVal']);
    }

    public function testThrowIfSendEventToMessageBusWithProcessorNamePropertySet()
    {
        $message = new Message();
        $message->setBody('');
        $message->setProperty(Config::PROCESSOR, 'aProcessor');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor property must not be set.');
        $producer->sendEvent('topic', $message);
    }

    public function testShouldSendEventToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('aBody', $message->getBody());

                // null means a driver sends a message to router processor.
                self::assertNull($message->getProperty(Config::PROCESSOR));

                return $this->createDriverSendResult();
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendEvent('topic', $message);
    }

    public function testThrowWhenProcessorNamePropertySetToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);
        $message->setProperty(Config::PROCESSOR, 'aCustomProcessor');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor property must not be set.');
        $producer->sendEvent('topic', $message);
    }

    public function testThrowIfUnSupportedScopeGivenOnSend()
    {
        $message = new Message();
        $message->setScope('iDontKnowScope');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message scope "iDontKnowScope" is not supported.');
        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreSendEventExtensionMethodWhenSendToBus()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_MESSAGE_BUS);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onPreSendEvent')
            ->willReturnCallback(function (PreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertEquals($message, $context->getOriginalMessage());
                $this->assertNotSame($message, $context->getOriginalMessage());
            });

        $extension
            ->expects($this->never())
            ->method('onPreSendCommand')
        ;

        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreSendEventExtensionMethodWhenSendToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onPreSendEvent')
            ->willReturnCallback(function (PreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertEquals($message, $context->getOriginalMessage());
                $this->assertNotSame($message, $context->getOriginalMessage());
            });

        $extension
            ->expects($this->never())
            ->method('onPreSendCommand')
        ;

        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreDriverSendExtensionMethodWhenSendToMessageBus()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_MESSAGE_BUS);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onDriverPreSend')
            ->willReturnCallback(function (DriverPreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertTrue($context->isEvent());
            });

        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreDriverSendExtensionMethodWhenSendToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onDriverPreSend')
            ->willReturnCallback(function (DriverPreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertTrue($context->isEvent());
            });

        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPostSendExtensionMethodWhenSendToMessageBus()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_MESSAGE_BUS);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onPostSend')
            ->willReturnCallback(function (PostSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertTrue($context->isEvent());
            });

        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPostSendExtensionMethodWhenSendToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturn($this->createDriverSendResult())
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects(self::once())
            ->method('onDriverPreSend')
            ->willReturnCallback(function (PostSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('topic', $context->getTopic());

                $this->assertTrue($context->isEvent());
            });

        $producer->sendEvent('topic', $message);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createRpcFactoryMock(): RpcFactory
    {
        return $this->createMock(RpcFactory::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverStub(): DriverInterface
    {
        $config = new Config(
            'a_prefix',
            '.',
            'an_app',
            'a_router_topic',
            'a_router_queue',
            'a_default_processor_queue',
            'a_router_processor_name',
            [],
            []
        );

        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        return $driverMock;
    }

    private function createDriverSendResult(): DriverSendResult
    {
        return new DriverSendResult(
            $this->createMock(Destination::class),
            $this->createMock(TransportMessage::class)
        );
    }
}
