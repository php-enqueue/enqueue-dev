# Quick tour
 
* [Transport](#transport)
* [Consumption](#consumption)
* [Remote Procedure Call (RPC)](#remote-procedure-call-rpc)
* [Client](#client)
* [Job queue](#job-queue)
* [Cli commands](#cli-commands)

## Transport

The transport layer or PSR (Enqueue message service) is a Message Oriented Middleware for sending messages between two or more clients. 
It is a messaging component that allows applications to create, send, receive, and read messages. 
It allows the communication between different components of a distributed application to be loosely coupled, reliable, and asynchronous.

PSR is inspired by JMS (Java Message Service). We tried to be as close as possible to [JSR 914](https://docs.oracle.com/javaee/7/api/javax/jms/package-summary.html) specification.
For now it supports [AMQP](https://www.rabbitmq.com/tutorials/amqp-concepts.html) and [STOMP](https://stomp.github.io/) message queue protocols.
You can connect to many modern brokers such as [RabbitMQ](https://www.rabbitmq.com/), [ActiveMQ](http://activemq.apache.org/) and others. 

Produce a message:

```php
<?php
use Enqueue\Psr\ConnectionFactory;

/** @var ConnectionFactory $connectionFactory **/
$psrContext = $connectionFactory->createContext();

$destination = $psrContext->createQueue('foo');
//$destination = $context->createTopic('foo');

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($destination, $message);
```

Consume a message:

```php
<?php
use Enqueue\Psr\ConnectionFactory;

/** @var ConnectionFactory $connectionFactory **/
$psrContext = $connectionFactory->createContext();

$destination = $psrContext->createQueue('foo');
//$destination = $context->createTopic('foo');

$consumer = $psrContext->createConsumer($destination);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Consumption 

Consumption is a layer build on top of a transport functionality. 
The goal of the component is to simply message consumption. 
The `QueueConsumer` is main piece of the component it allows bind message processors (or callbacks) to queues. 
The `consume` method starts the consumption process which last as long as it is interrupted.

```php
<?php
use Enqueue\Psr\Message;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;

/** @var \Enqueue\Psr\Context $psrContext */

$queueConsumer = new QueueConsumer($psrContext);

$queueConsumer->bind('foo_queue', function(Message $message) {
    // process messsage
    
    return Result::ACK;
});
$queueConsumer->bind('bar_queue', function(Message $message) {
    // process messsage
    
    return Result::ACK;
});

$queueConsumer->consume();
```

There are bunch of [extensions](consumption/extensions.md) available. 
This is an example of how you can add them. 
The `SignalExtension` provides support of process signals, whenever you send SIGTERM for example it will correctly managed.
The `LimitConsumptionTimeExtension` interrupts the consumption after given time. 

```php
<?php
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;

/** @var \Enqueue\Psr\Context $psrContext */

$queueConsumer = new QueueConsumer($psrContext, new ChainExtension([
    new SignalExtension(),
    new LimitConsumptionTimeExtension(new \DateTime('now + 60 sec')),
]));
```

## Remote Procedure Call (RPC)

There is RPC component that allows you send RPC requests over MQ easily.
You can do several calls asynchronously. This is how you can send a RPC message and wait for a reply message.

```php
<?php
use Enqueue\Rpc\RpcClient;

/** @var \Enqueue\Psr\Context $psrContext */

$queue = $psrContext->createQueue('foo');
$message = $psrContext->createMessage('Hi there!');

$rpcClient = new RpcClient($psrContext);

$promise = $rpcClient->callAsync($queue, $message, 1);
$replyMessage = $promise->getMessage();
```

There is also extensions for the consumption component. 
It simplifies a server side of RPC.

```php
<?php
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

/** @var \Enqueue\Psr\Context $psrContext */

$queueConsumer = new QueueConsumer($psrContext, new ChainExtension([
    new ReplyExtension()
]));

$queueConsumer->bind('foo', function(Message $message, Context $context) {
    $replyMessage = $context->createMessage('Hello');
    
    return Result::reply($replyMessage);
});

$queueConsumer->consume();
```

## Client

It provides a high level abstraction.
The goal of the component is hide as much as possible details from you so you can concentrate on things that really matters. 
For example, It configure a broker for you, if needed.
It provides easy to use services for producing and processing messages.
It supports message bus so different applications can send message to each other.
 
Here's an example of how you can send and consume messages.
 
```php
<?php
use Enqueue\Client\SimpleClient;
use Enqueue\Consumption\Result;
use Enqueue\Psr\Message;

/** @var \Enqueue\Psr\Context $psrClient */

$client = new SimpleClient($psrClient);
$client->bind('foo_topic', function (Message $message) {
    // process message

    return Result::ACK;
});

$client->send('foo_topic', 'Hello there!');

// in another process you can consume messages. 
$client->consume();
```

## Job queue

There is job queue component build on top of a transport. It provides some additional features:

* Stores jobs to a database. So you can query that information and build a UI for it.
* Run unique job feature. If used guarantee that there is not any job with the same name running same time.
* Sub jobs. If used allow split a big job into smaller pieces and process them asynchronously and in parallel.
* Depended job. If used allow send a message when the whole job is finished (including sub jobs).
  
Here's some  examples.
First shows how you can run unique job using job queue (The configuration is described in a dedicated chapter). 

```php
<?php 
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\JobQueue\JobRunner;

class MessageProcessor implements MessageProcessorInterface
{
    /** @var JobRunner */
    private $jobRunner;

    public function process(Message $message, Context $context)
    {
        $result = $this->jobRunner->runUnique($message->getMessageId(), 'aJobName', function () {
            // do your job, there is no any other processes executing same job,

            return true; // if you want to ACK message or false to REJECT
        });

        return $result ? Result::ACK : Result::REJECT;
    }
}
```

Second shows how you can create and run a sub job, which it is executed separately. 
You can create as many sub jobs as you like. 
They will be executed in parallel. 

```php
<?php
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Client\MessageProducerInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Util\JSON;

class RootJobMessageProcessor implements MessageProcessorInterface
{
    /** @var JobRunner */
    private $jobRunner;
    
    /** @var  MessageProducerInterface */
    private $producer;

    public function process(Message $message, Context $context)
    {
        $result = $this->jobRunner->runUnique($message->getMessageId(), 'aJobName', function (JobRunner $runner) {
            $runner->createDelayed('aSubJobName1', function (JobRunner $runner, Job $childJob) {
                $this->producer->send('aJobTopic', [
                    'jobId' => $childJob->getId(),
                    // other data required by sub job
                ]);
            });

            return true;
        });

        return $result ? Result::ACK : Result::REJECT;
    }
}

class SubJobMessageProcessor implements MessageProcessorInterface
{
    /** @var JobRunner */
    private $jobRunner;

    public function process(Message $message, Context $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($data) {
            // do your job

            return true;
        });

        return $result ? Result::ACK : Result::REJECT;
    }
}
```

## Cli commands

The library provides handy commands out of the box. 
They all build on top of [Symfony Console component](http://symfony.com/doc/current/components/console.html). 
The most useful is a consume command. There are two of them one from consumption component and the other from client one.

Let's see how you can use consumption one:

```php
#!/usr/bin/env php
<?php
// app.php

use Symfony\Component\Console\Application;
use Enqueue\Psr\Message;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Symfony\Consumption\ConsumeMessagesCommand;

/** @var QueueConsumer $queueConsumer */

$queueConsumer->bind('a_queue', function(Message $message) {
    // process message    
});

$consumeCommand = new ConsumeMessagesCommand($queueConsumer);
$consumeCommand->setName('consume');

$app = new Application();
$app->add($consumeCommand);
$app->run();
```

and starts the consumption from the console:
 
```bash
$ app.php consume --time-limit="now + 60 sec" --message-limit=10 --memory-limit=256
```

[back to index](index.md)
