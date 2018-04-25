<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Null\NullQueue;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        self::assertClassImplements(ProducerInterface::class, Producer::class);
    }

    public function testCouldBeConstructedWithDriverAsFirstArgument()
    {
        new Producer($this->createDriverStub(), $this->createRpcFactory());
    }

    public function testShouldSendMessageToRouter()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        $expectedProperties = [
            'enqueue.topic_name' => 'topic',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendMessageWithNormalPriorityByDefault()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertSame(MessagePriority::NORMAL, $message->getPriority());
    }

    public function testShouldSendMessageWithCustomPriority()
    {
        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertSame(MessagePriority::HIGH, $message->getPriority());
    }

    public function testShouldSendMessageWithGeneratedMessageId()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertNotEmpty($message->getMessageId());
    }

    public function testShouldSendMessageWithCustomMessageId()
    {
        $message = new Message();
        $message->setMessageId('theCustomMessageId');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertSame('theCustomMessageId', $message->getMessageId());
    }

    public function testShouldSendMessageWithGeneratedTimestamp()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertNotEmpty($message->getTimestamp());
    }

    public function testShouldSendMessageWithCustomTimestamp()
    {
        $message = new Message();
        $message->setTimestamp('theCustomTimestamp');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);

        self::assertSame('theCustomTimestamp', $message->getTimestamp());
    }

    public function testShouldSendStringAsPlainText()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('theStringMessage', $message->getBody());
                self::assertSame('text/plain', $message->getContentType());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', 'theStringMessage');
    }

    public function testShouldSendArrayAsJsonString()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', ['foo' => 'fooVal']);
    }

    public function testShouldConvertMessageArrayBodyJsonString()
    {
        $message = new Message();
        $message->setBody(['foo' => 'fooVal']);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);
    }

    public function testSendShouldForceScalarsToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('12345', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent($queue, 12345);
    }

    public function testSendShouldForceMessageScalarsBodyToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new Message();
        $message->setBody(12345);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('12345', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent($queue, $message);
    }

    public function testSendShouldForceNullToEmptyStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent($queue, null);
    }

    public function testSendShouldForceNullBodyToEmptyStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new Message();
        $message->setBody(null);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent($queue, $message);
    }

    public function testShouldThrowExceptionIfBodyIsObjectOnSend()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The message\'s body must be either null, scalar, array or object (implements \JsonSerializable). Got: stdClass');

        $producer->sendEvent('topic', new \stdClass());
    }

    public function testShouldThrowExceptionIfBodyIsArrayWithObjectsInsideOnSend()
    {
        $queue = new NullQueue('queue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message\'s body must be an array of scalars. Found not scalar in the array: stdClass');

        $producer->sendEvent($queue, ['foo' => new \stdClass()]);
    }

    public function testShouldThrowExceptionIfBodyIsArrayWithObjectsInSubArraysInsideOnSend()
    {
        $queue = new NullQueue('queue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message\'s body must be an array of scalars. Found not scalar in the array: stdClass');

        $producer->sendEvent($queue, ['foo' => ['bar' => new \stdClass()]]);
    }

    public function testShouldSendJsonSerializableObjectAsJsonStringToMessageBus()
    {
        $object = new JsonSerializableObject();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $object);
    }

    public function testShouldSendMessageJsonSerializableBodyAsJsonStringToMessageBus()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);
    }

    public function testThrowIfTryToSendMessageToMessageBusWithProcessorNamePropertySet()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aProcessor');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor_name property must not be set for messages that are sent to message bus.');
        $producer->sendEvent('topic', $message);
    }

    public function testThrowIfTryToSendMessageToMessageBusWithProcessorQueueNamePropertySet()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aProcessorQueue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor_queue_name property must not be set for messages that are sent to message bus.');
        $producer->sendEvent('topic', $message);
    }

    public function testThrowIfNotApplicationJsonContentTypeSetWithJsonSerializableBody()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setContentType('foo/bar');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content type "application/json" only allowed when body is array');

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);
    }

    public function testShouldSendMessageToApplicationRouter()
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
                self::assertSame('a_router_processor_name', $message->getProperty(Config::PARAMETER_PROCESSOR_NAME));
                self::assertSame('a_router_queue', $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
        $producer->sendEvent('topic', $message);
    }

    public function testShouldSendToCustomMessageToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aCustomProcessor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aCustomProcessorQueue');

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
                self::assertSame('aCustomProcessor', $message->getProperty(Config::PARAMETER_PROCESSOR_NAME));
                self::assertSame('aCustomProcessorQueue', $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactory());
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

        $producer = new Producer($driver, $this->createRpcFactory());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message scope "iDontKnowScope" is not supported.');
        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreSendPostSendExtensionMethodsWhenSendToRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_MESSAGE_BUS);

        $extension = $this->createMock(ExtensionInterface::class);
        $extension
            ->expects($this->at(0))
            ->method('onPreSend')
            ->with($this->identicalTo('topic'), $this->identicalTo($message))
        ;
        $extension
            ->expects($this->at(1))
            ->method('onPostSend')
            ->with($this->identicalTo('topic'), $this->identicalTo($message))
        ;

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
        ;

        $producer = new Producer($driver, $this->createRpcFactory(), $extension);
        $producer->sendEvent('topic', $message);
    }

    public function testShouldCallPreSendPostSendExtensionMethodsWhenSendToProcessor()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $extension = $this->createMock(ExtensionInterface::class);
        $extension
            ->expects($this->at(0))
            ->method('onPreSend')
            ->with($this->identicalTo('topic'), $this->identicalTo($message))
        ;
        $extension
            ->expects($this->at(1))
            ->method('onPostSend')
            ->with($this->identicalTo('topic'), $this->identicalTo($message))
        ;

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactory(), $extension);
        $producer->sendEvent('topic', $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RpcFactory
     */
    private function createRpcFactory()
    {
        return $this->createMock(RpcFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverStub()
    {
        $config = new Config(
            'a_prefix',
            'an_app',
            'a_router_topic',
            'a_router_queue',
            'a_default_processor_queue',
            'a_router_processor_name'
        );

        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        return $driverMock;
    }
}

class JsonSerializableObject implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'fooVal'];
    }
}
