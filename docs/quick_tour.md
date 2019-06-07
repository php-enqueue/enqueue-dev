---
layout: default
title: Quick tour
nav_order: 2
---
{% include support.md %}

# Quick tour

* [Transport](#transport)
* [Consumption](#consumption)
* [Remote Procedure Call (RPC)](#remote-procedure-call-rpc)
* [Client](#client)
* [Cli commands](#cli-commands)
* [Monitoring](#monitoring)
* [Symfony bundle](#symfony)

## Transport

The transport layer or PSR (Enqueue message service) is a Message Oriented Middleware for sending messages between two or more clients.
It is a messaging component that allows applications to create, send, receive, and read messages.
It allows the communication between different components of a distributed application to be loosely coupled, reliable, and asynchronous.

PSR is inspired by JMS (Java Message Service). We tried to stay as close as possible to the [JSR 914](https://docs.oracle.com/javaee/7/api/javax/jms/package-summary.html) specification.
For now it supports [AMQP](https://www.rabbitmq.com/tutorials/amqp-concepts.html) and [STOMP](https://stomp.github.io/) message queue protocols.
You can connect to many modern brokers such as [RabbitMQ](https://www.rabbitmq.com/), [ActiveMQ](http://activemq.apache.org/) and others.

Produce a message:

```php
<?php
use Interop\Queue\ConnectionFactory;

/** @var ConnectionFactory $connectionFactory **/
$context = $connectionFactory->createContext();

$destination = $context->createQueue('foo');
//$destination = $context->createTopic('foo');

$message = $context->createMessage('Hello world!');

$context->createProducer()->send($destination, $message);
```

Consume a message:

```php
<?php
use Interop\Queue\ConnectionFactory;

/** @var ConnectionFactory $connectionFactory **/
$context = $connectionFactory->createContext();

$destination = $context->createQueue('foo');
//$destination = $context->createTopic('foo');

$consumer = $context->createConsumer($destination);

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
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\Context;
use Enqueue\Consumption\QueueConsumer;

/** @var Context $context */

$queueConsumer = new QueueConsumer($context);

$queueConsumer->bindCallback('foo_queue', function(Message $message) {
    // process message

    return Processor::ACK;
});
$queueConsumer->bindCallback('bar_queue', function(Message $message) {
    // process message

    return Processor::ACK;
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

/** @var \Interop\Queue\Context $context */

$queueConsumer = new QueueConsumer($context, new ChainExtension([
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

/** @var \Interop\Queue\Context $context */

$queue = $context->createQueue('foo');
$message = $context->createMessage('Hi there!');

$rpcClient = new RpcClient($context);

$promise = $rpcClient->callAsync($queue, $message, 1);
$replyMessage = $promise->receive();
```

There is also extensions for the consumption component.
It simplifies a server side of RPC.

```php
<?php
use Interop\Queue\Message;
use Interop\Queue\Context;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

/** @var \Interop\Queue\Context $context */

$queueConsumer = new QueueConsumer($context, new ChainExtension([
    new ReplyExtension()
]));

$queueConsumer->bindCallback('foo', function(Message $message, Context $context) {
    $replyMessage = $context->createMessage('Hello');

    return Result::reply($replyMessage);
});

$queueConsumer->consume();
```

## Client

It provides an easy to use high level abstraction.
The goal of the component is to hide as much as possible low level details so you can concentrate on things that really matter.
For example, it configures a broker for you by creating queues, exchanges and bind them.
It provides easy to use services for producing and processing messages.
It supports unified format for setting message expiration, delay, timestamp, correlation id.
It supports [message bus](http://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html) so different applications can talk to each other.

Here's an example of how you can send and consume **event messages**.

```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\Message;

// composer require enqueue/amqp-ext
$client = new SimpleClient('amqp:');

// composer require enqueue/fs
$client = new SimpleClient('file://foo/bar');
$client->bindTopic('a_foo_topic', function(Message $message) {
    echo $message->getBody().PHP_EOL;

    // your event processor logic here
});

$client->setupBroker();

$client->sendEvent('a_foo_topic', 'message');

// this is a blocking call, it'll consume message until it is interrupted
$client->consume();
```

and **command messages**:

```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Enqueue\Client\Config;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

// composer require enqueue/amqp-ext # or enqueue/amqp-bunny or enqueue/amqp-lib
$client = new SimpleClient('amqp:');

// composer require enqueue/fs
//$client = new SimpleClient('file://foo/bar');

$client->bindCommand('bar_command', function(Message $message) {
    // your bar command processor logic here
});

$client->bindCommand('baz_reply_command', function(Message $message, Context $context) {
    // your baz reply command processor logic here

    return Result::reply($context->createMessage('theReplyBody'));
});

$client->setupBroker();

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
use Interop\Queue\Message;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Symfony\Consumption\SimpleConsumeCommand;

/** @var QueueConsumer $queueConsumer */

$queueConsumer->bindCallback('a_queue', function(Message $message) {
    // process message
});

$consumeCommand = new SimpleConsumeCommand($queueConsumer);
$consumeCommand->setName('consume');

$app = new Application();
$app->add($consumeCommand);
$app->run();
```

and starts the consumption from the console:

```bash
$ app.php consume
```

## Monitoring

There is a tool that can track sent\consumed messages as well as consumer performance. Read more [here](monitoring.md)

[back to index](index.md)

## Symfony

Read more [here](bundle/quick_tour.md) about using Enqueue as a Symfony Bundle.
