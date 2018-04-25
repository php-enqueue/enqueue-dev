# Quick tour
 
* [Transport](#transport)
* [Consumption](#consumption)
* [Remote Procedure Call (RPC)](#remote-procedure-call-rpc)
* [Client](#client)
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
use Interop\Queue\PsrConnectionFactory;

/** @var PsrConnectionFactory $connectionFactory **/
$psrContext = $connectionFactory->createContext();

$destination = $psrContext->createQueue('foo');
//$destination = $context->createTopic('foo');

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($destination, $message);
```

Consume a message:

```php
<?php
use Interop\Queue\PsrConnectionFactory;

/** @var PsrConnectionFactory $connectionFactory **/
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

Consumption is a layer built on top of a transport functionality. 
The goal of the component is to simply consume messages. 
The `QueueConsumer` is main piece of the component it allows binding of message processors (or callbacks) to queues. 
The `consume` method starts the consumption process which last as long as it is not interrupted.

```php
<?php
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrContext;
use Enqueue\Consumption\QueueConsumer;

/** @var PsrContext $psrContext */

$queueConsumer = new QueueConsumer($psrContext);

$queueConsumer->bind('foo_queue', function(PsrMessage $message) {
    // process message
    
    return PsrProcessor::ACK;
});
$queueConsumer->bind('bar_queue', function(PsrMessage $message) {
    // process message
    
    return PsrProcessor::ACK;
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

/** @var \Interop\Queue\PsrContext $psrContext */

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

/** @var \Interop\Queue\PsrContext $psrContext */

$queue = $psrContext->createQueue('foo');
$message = $psrContext->createMessage('Hi there!');

$rpcClient = new RpcClient($psrContext);

$promise = $rpcClient->callAsync($queue, $message, 1);
$replyMessage = $promise->receive();
```

There is also extensions for the consumption component. 
It simplifies a server side of RPC.

```php
<?php
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

/** @var \Interop\Queue\PsrContext $psrContext */

$queueConsumer = new QueueConsumer($psrContext, new ChainExtension([
    new ReplyExtension()
]));

$queueConsumer->bind('foo', function(PsrMessage $message, PsrContext $context) {
    $replyMessage = $context->createMessage('Hello');
    
    return Result::reply($replyMessage);
});

$queueConsumer->consume();
```

## Client

It provides an easy to use high level abstraction.
The goal of the component is hide as much as possible low level details so you can concentrate on things that really matters. 
For example, It configure a broker for you by creating queues, exchanges and bind them.
It provides easy to use services for producing and processing messages. 
It supports unified format for setting message expiration, delay, timestamp, correlation id.
It supports [message bus](http://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html) so different applications can talk to each other.
 
Here's an example of how you can send and consume **event messages**.
 
```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\PsrMessage;

// composer require enqueue/amqp-ext
$client = new SimpleClient('amqp:');

// composer require enqueue/fs
$client = new SimpleClient('file://foo/bar');

$client->setupBroker();

$client->sendEvent('a_foo_topic', 'message');

$client->bind('a_foo_topic', 'fooProcessor', function(PsrMessage $message) {
    echo $message->getBody().PHP_EOL;
    
    // your event processor logic here
});

// this is a blocking call, it'll consume message until it is interrupted 
$client->consume();
```

and **command messages**: 

```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Enqueue\Client\Config;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

// composer require enqueue/amqp-ext # or enqueue/amqp-bunny or enqueue/amqp-lib 
$client = new SimpleClient('amqp:');

// composer require enqueue/fs
//$client = new SimpleClient('file://foo/bar');

$client->setupBroker();

$client->bind(Config::COMMAND_TOPIC, 'bar_command', function(PsrMessage $message) {
    // your bar command processor logic here
});

$client->bind(Config::COMMAND_TOPIC, 'baz_reply_command', function(PsrMessage $message, PsrContext $context) {
    // your baz reply command processor logic here
    
    return Result::reply($context->createMessage('theReplyBody'));
});

// It is sent to one consumer.  
$client->sendCommand('bar_command', 'aMessageData');

// It is possible to get reply
$promise = $client->sendCommand('bar_command', 'aMessageData', true);

// you can send several commands and only after start getting replies.

$replyMessage = $promise->receive(2000); // 2 sec

// this is a blocking call, it'll consume message until it is interrupted 
$client->consume([new ReplyExtension()]);
```

Read more about events and commands [here](client/quick_tour.md#produce-message). 

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
use Interop\Queue\PsrMessage;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Symfony\Consumption\ConsumeMessagesCommand;

/** @var QueueConsumer $queueConsumer */

$queueConsumer->bind('a_queue', function(PsrMessage $message) {
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
$ app.php consume
```

[back to index](index.md)
