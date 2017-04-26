<?php

namespace Enqueue\Tests\Functional\Client;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Client\RpcClient;
use Enqueue\Client\SimpleClient;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Test\RabbitmqAmqpExtension;
use Enqueue\Test\RabbitmqManagmentExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class RpcClientTest extends TestCase
{
    use RabbitmqAmqpExtension;
    use RabbitmqManagmentExtensionTrait;

    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AmqpContext
     */
    private $replyContext;

    public function setUp()
    {
        $this->context = $this->buildAmqpContext();
        $this->replyContext = $this->buildAmqpContext();

        $this->removeQueue('default');
    }

    public function testProduceAndConsumeOneMessage()
    {
        $requestMessage = null;

        $client = new SimpleClient($this->context);
        $client->bind('foo_topic', 'foo_processor', function (PsrMessage $message, PsrContext $context) use (&$requestMessage) {
            $requestMessage = $message;

            return Result::reply($context->createMessage('Hi John!'));
        });

        $rpcClient = new RpcClient($client->getProducer(), $this->replyContext);
        $promise = $rpcClient->callAsync('foo_topic', 'Hi Thomas!', 5);

        $client->consume(new ChainExtension([
            new ReplyExtension(),
            new LimitConsumptionTimeExtension(new \DateTime('+5sec')),
            new LimitConsumedMessagesExtension(2),
        ]));

        //guard
        $this->assertInstanceOf(PsrMessage::class, $requestMessage);
        $this->assertEquals('Hi Thomas!', $requestMessage->getBody());

        $replyMessage = $promise->getMessage();
        $this->assertEquals('Hi John!', $replyMessage->getBody());
    }
}
