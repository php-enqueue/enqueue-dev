<?php

namespace Enqueue\Sqs\Tests;

use Aws\Result;
use Enqueue\Sqs\SqsClient;
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
use PHPUnit\Framework\TestCase;

class SqsContextTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, SqsContext::class);
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
            ->with($this->identicalTo([
                '@region' => null,
                'Attributes' => [],
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');

        $context->declareQueue($queue);
    }

    public function testShouldAllowDeclareQueueWithCustomRegion()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'Attributes' => [],
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');
        $queue->setRegion('theRegion');

        $context->declareQueue($queue);
    }

    public function testShouldAllowDeleteQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueName' => 'aQueueName',
            ]))
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

    public function testShouldAllowDeleteQueueWithCustomRegion()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueName' => 'aQueueName',
            ]))
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
        $queue->setRegion('theRegion');

        $context->deleteQueue($queue);
    }

    public function testShouldAllowPurgeQueue()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;
        $sqsClient
            ->expects($this->once())
            ->method('purgeQueue')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueUrl' => 'theQueueUrl',
            ]))
            ->willReturn(new Result())
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');

        $context->purgeQueue($queue);
    }

    public function testShouldAllowPurgeQueueWithCustomRegion()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;
        $sqsClient
            ->expects($this->once())
            ->method('purgeQueue')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
            ]))
            ->willReturn(new Result())
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = $context->createQueue('aQueueName');
        $queue->setRegion('theRegion');

        $context->purgeQueue($queue);
    }

    public function testShouldAllowGetQueueUrl()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => null,
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $context->getQueueUrl(new SqsDestination('aQueueName'));
    }

    public function testShouldAllowGetQueueArn()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;
        $sqsClient
            ->expects($this->once())
            ->method('getQueueAttributes')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueUrl' => 'theQueueUrl',
                'AttributeNames' => ['QueueArn'],
            ]))
            ->willReturn(new Result([
                'Attributes' => [
                    'QueueArn' => 'theQueueArn',
                ],
            ]))
        ;

        $context = new SqsContext($sqsClient, []);

        $queue = $context->createQueue('aQueueName');
        $queue->setRegion('theRegion');

        $this->assertSame('theQueueArn', $context->getQueueArn($queue));
    }

    public function testShouldAllowGetQueueUrlWithCustomRegion()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => 'theRegion',
                'QueueName' => 'aQueueName',
            ]))
            ->willReturn(new Result(['QueueUrl' => 'theQueueUrl']))
        ;

        $context = new SqsContext($sqsClient, [
            'queue_owner_aws_account_id' => null,
        ]);

        $queue = new SqsDestination('aQueueName');
        $queue->setRegion('theRegion');

        $context->getQueueUrl($queue);
    }

    public function testShouldAllowGetQueueUrlFromAnotherAWSAccountSetGlobally()
    {
        $sqsClient = $this->createSqsClientMock();
        $sqsClient
            ->expects($this->once())
            ->method('getQueueUrl')
            ->with($this->identicalTo([
                '@region' => null,
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
                '@region' => null,
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
            ->with($this->identicalTo([
                '@region' => null,
                'QueueName' => 'aQueueName',
            ]))
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
     * @return \PHPUnit\Framework\MockObject\MockObject|SqsClient
     */
    private function createSqsClientMock(): SqsClient
    {
        return $this->createMock(SqsClient::class);
    }
}
