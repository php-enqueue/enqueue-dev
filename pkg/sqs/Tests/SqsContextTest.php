<?php

namespace Enqueue\Sqs\Tests;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Enqueue\Sqs\SqsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Queue;

class SqsContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, SqsContext::class);
    }

    public function testCouldBeConstructedWithSqsClientAsFirstArgument()
    {
        new SqsContext($this->createSqsClientMock(), []);
    }

    public function testCouldBeConstructedWithSqsClientFactoryAsFirstArgument()
    {
        new SqsContext(function () {
            return $this->createSqsClientMock();
        }, []);
    }

    public function testThrowIfNeitherSqsClientNorFactoryGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $client argument must be either Aws\Sqs\SqsClient or callable that returns Aws\Sqs\SqsClient once called.');
        new SqsContext(new \stdClass(), []);
    }

    public function testShouldAllowCreateEmptyMessage()
    {
        $context = new SqsContext($this->createSqsClientMock(), []);

        $message = $context->createMessage();

        $this->assertInstanceOf(SqsMessage::class, $message);

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new SqsContext($this->createSqsClientMock(), []);

        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $this->assertInstanceOf(SqsMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateQueue()
    {
        $context = new SqsContext($this->createSqsClientMock(), [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueue');

        $this->assertInstanceOf(SqsDestination::class, $queue);
        $this->assertSame('aQueue', $queue->getQueueName());
    }

    public function testShouldAllowCreateTopic()
    {
        $context = new SqsContext($this->createSqsClientMock(), [
            'queue_owner_aws_account_id' => null,
        ]);

        $topic = $context->createTopic('aTopic');

        $this->assertInstanceOf(SqsDestination::class, $topic);
        $this->assertSame('aTopic', $topic->getTopicName());
    }

    public function testThrowNotImplementedOnCreateTmpQueueCall()
    {
        $context = new SqsContext($this->createSqsClientMock(), []);

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    public function testShouldCreateProducer()
    {
        $context = new SqsContext($this->createSqsClientMock(), []);

        $producer = $context->createProducer();

        $this->assertInstanceOf(SqsProducer::class, $producer);
    }

    public function testShouldThrowIfNotSqsDestinationGivenOnCreateConsumer()
    {
        $context = new SqsContext($this->createSqsClientMock(), []);

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Sqs\SqsDestination but got Mock_Queue');

        $context->createConsumer($this->createMock(Queue::class));
    }

    public function testShouldCreateConsumer()
    {
        $context = new SqsContext($this->createSqsClientMock(), [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueue');

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(SqsConsumer::class, $consumer);
    }

    public function testShouldAllowDeclareQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->identicalTo(['Attributes' => [], 'QueueName' => 'aQueueName']))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');

        $context->declareQueue($queue);
    }

    public function testShouldAllowDeleteQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo(['QueueName' => 'aQueueName']))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;
        $sqsClient
            ->expects($this->once())
            ->method('deleteQueue')
            ->with($this->identicalTo(['QueueUrl' => 'theQueueUrl']))
            ->willReturn(new Result())
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');

        $context->deleteQueue($queue);
    }

    public function testShouldAllowPurgeQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo(['QueueName' => 'aQueueName']))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;
        $sqsClient
            ->expects($this->once())
            ->method('purgeQueue')
            ->with($this->identicalTo(['QueueUrl' => 'theQueueUrl']))
            ->willReturn(new Result())
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');

        $context->purgeQueue($queue);
    }

    public function testShouldAllowGetQueueUrl()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo(['QueueName' => 'aQueueName']))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $context->getQueueUrl(new SqsDestination('aQueueName'));
    }

    public function testShouldAllowGetQueueUrlFromAnotherAWSAccountSetGlobally()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                'QueueName' => 'aQueueName',
                'QueueOwnerAWSAccountId' => 'anotherAWSAccountID',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => 'anotherAWSAccountID',
        ]);

        $context->getQueueUrl(new SqsDestination('aQueueName'));
    }

    public function testShouldAllowGetQueueUrlFromAnotherAWSAccountSetPerQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                'QueueName' => 'aQueueName',
                'QueueOwnerAWSAccountId' => 'anotherAWSAccountID',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = new SqsDestination('aQueueName');
        $queue->setQueueOwnerAWSAccountId('anotherAWSAccountID');

        $context->getQueueUrl($queue);
    }

    public function testShouldThrowExceptionIfGetQueueUrlResultHasNoQueueUrlProperty()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo(['QueueName' => 'aQueueName']))
            ->willReturn(new Result([]))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('QueueUrl cannot be resolved. queueName: "aQueueName"');

        $context->getQueueUrl(new SqsDestination('aQueueName'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsClient
     */
    private function createSqsClientMock()
    {
        return $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteQueue', 'purgeQueue', 'createQueue', 'getQueueUrl'])
            ->getMock()
        ;
    }
}
