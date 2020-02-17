---
layout: default
title: AMQP Lib
parent: Transports
nav_order: 3
---
{% include support.md %}

# AMQP transport

Implements [AMQP specifications](https://www.rabbitmq.com/specification.html) and implements [amqp interop](https://github.com/queue-interop/amqp-interop) interfaces.
Build on top of [php amqp lib](https://github.com/php-amqplib/php-amqplib).

Features:
* Configure with DSN string
* Delay strategies out of the box
* Interchangeable with other AMQP Interop implementations
* Fixes AMQPIOWaitException when signal is sent.
* More reliable heartbeat implementations.
* Supports Subscription consumer

Parts:
* [Installation](#installation)
* [Create context](#create-context)
* [Declare topic](#declare-topic)
* [Declare queue](#decalre-queue)
* [Bind queue to topic](#bind-queue-to-topic)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send priority message](#send-priority-message)
* [Send expiration message](#send-expiration-message)
* [Send delayed message](#send-delayed-message)
* [Consume message](#consume-message)
* [Subscription consumer](#subscription-consumer)
* [Purge queue messages](#purge-queue-messages)
* [Long running task and heartbeat and timeouts](#long-running-task-and-heartbeat-and-timeouts)

## Installation

```bash
$ composer require enqueue/amqp-lib
```

## Create context

```php
<?php
use Enqueue\AmqpLib\AmqpConnectionFactory;

// connects to localhost
$factory = new AmqpConnectionFactory();

// same as above
$factory = new AmqpConnectionFactory('amqp:');

// same as above
$factory = new AmqpConnectionFactory([]);

// connect to AMQP broker at example.com
$factory = new AmqpConnectionFactory([
    'host' => 'example.com',
    'port' => 1000,
    'vhost' => '/',
    'user' => 'user',
    'pass' => 'pass',
    'persisted' => false,
]);

// same as above but given as DSN string
$factory = new AmqpConnectionFactory('amqp://user:pass@example.com:10000/%2f');

// SSL or secure connection
$factory = new AmqpConnectionFactory([
    'dsn' => 'amqps:',
    'ssl_cacert' => '/path/to/cacert.pem',
    'ssl_cert' => '/path/to/cert.pem',
    'ssl_key' => '/path/to/key.pem',
]);

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('amqp:')->createContext();
$context = (new \Enqueue\ConnectionFactoryFactory())->create('amqp+lib:')->createContext();
```

## Declare topic.

Declare topic operation creates a topic on a broker side.

```php
<?php
use Interop\Amqp\AmqpTopic;

/** @var \Enqueue\AmqpLib\AmqpContext $context */

$fooTopic = $context->createTopic('foo');
$fooTopic->setType(AmqpTopic::TYPE_FANOUT);
$context->declareTopic($fooTopic);

// to remove topic use delete topic method
//$context->deleteTopic($fooTopic);
```

## Declare queue.

Declare queue operation creates a queue on a broker side.

```php
<?php
use Interop\Amqp\AmqpQueue;

/** @var \Enqueue\AmqpLib\AmqpContext $context */

$fooQueue = $context->createQueue('foo');
$fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
$context->declareQueue($fooQueue);

// to remove topic use delete queue method
//$context->deleteQueue($fooQueue);
```

## Bind queue to topic

Connects a queue to the topic. So messages from that topic comes to the queue and could be processed.

```php
<?php
use Interop\Amqp\Impl\AmqpBind;

/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */
/** @var \Interop\Amqp\Impl\AmqpTopic $fooTopic */

$context->bind(new AmqpBind($fooTopic, $fooQueue));
```

## Send message to topic

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpTopic $fooTopic */

$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send priority message

```php
<?php
use Interop\Amqp\AmqpQueue;

/** @var \Enqueue\AmqpLib\AmqpContext $context */

$fooQueue = $context->createQueue('foo');
$fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
$fooQueue->setArguments(['x-max-priority' => 10]);
$context->declareQueue($fooQueue);

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setPriority(5) // the higher priority the sooner a message gets to a consumer
    //
    ->send($fooQueue, $message)
;
```

## Send expiration message

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setTimeToLive(60000) // 60 sec
    //
    ->send($fooQueue, $message)
;
```

## Send delayed message

AMQP specification says nothing about message delaying hence the producer throws `DeliveryDelayNotSupportedException`.
Though the producer (and the context) accepts a delivery delay strategy and if it is set it uses it to send delayed message.
The `enqueue/amqp-tools` package provides two RabbitMQ delay strategies, to use them you have to install that package

```php
<?php
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;

/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

// make sure you run "composer require enqueue/amqp-tools".

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setDelayStrategy(new RabbitMqDlxDelayStrategy())
    ->setDeliveryDelay(5000) // 5 sec

    ->send($fooQueue, $message)
;
````

## Consume message:

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Subscription consumer

```php
<?php
use Interop\Queue\Message;
use Interop\Queue\Consumer;

/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */
/** @var \Interop\Amqp\Impl\AmqpQueue $barQueue */

$fooConsumer = $context->createConsumer($fooQueue);
$barConsumer = $context->createConsumer($barQueue);

$subscriptionConsumer = $context->createSubscriptionConsumer();
$subscriptionConsumer->subscribe($fooConsumer, function(Message $message, Consumer $consumer) {
    // process message

    $consumer->acknowledge($message);

    return true;
});
$subscriptionConsumer->subscribe($barConsumer, function(Message $message, Consumer $consumer) {
    // process message

    $consumer->acknowledge($message);

    return true;
});

$subscriptionConsumer->consume(2000); // 2 sec
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $context */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$queue = $context->createQueue('aQueue');

$context->purgeQueue($queue);
```

## Long running task and heartbeat and timeouts

AMQP relies on heartbeat feature to make sure consumer is still there.
Basically consumer is expected to send heartbeat frames from time to time to RabbitMQ broker so the broker does not close the connection.
It is not possible to implement heartbeat feature in PHP, due to its synchronous nature.
You could read more about the issues in post: [Keeping RabbitMQ connections alive in PHP](https://blog.mollie.com/keeping-rabbitmq-connections-alive-in-php-b11cb657d5fb).

`enqueue/amqp-lib` address the issue by registering heartbeat call as a [tick callbacks](http://php.net/manual/en/function.register-tick-function.php).
To make it work you have to wrapp your long running task by `declare(ticks=1) {}`.
The number of ticks could be adjusted to your needs.
Calling it at every tick is not good.

Please note that it does not fix heartbeat issue if you spent most of the time on IO operation.

Example:

```php
<?php

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpMessage;

$context = (new AmqpConnectionFactory('amqp:?heartbeat_on_tick=1'))->createContext();

$queue = $context->createQueue('a_queue');
$consumer = $context->createConsumer($queue);

$subscriptionConsumer = $context->createSubscriptionConsumer();
$subscriptionConsumer->subscribe($consumer, function(AmqpMessage $message, AmqpConsumer $consumer) {
    // ticks number should be adjusted.
    declare(ticks=1) {
        foreach (fetchHugeSet() as $item) {
            // cycle does something for a long time, much longer than amqp heartbeat.
        }
    }

    $consumer->acknowledge($message);

    return true;
});

$subscriptionConsumer->consume(10000);


function fetchHugeSet(): array {};
```

Fixes partly `Invalid frame type 65` issue.

```
Error: Uncaught PhpAmqpLib\Exception\AMQPRuntimeException: Invalid frame type 65 in /some/path/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Connection/AbstractConnection.php:528
```

Fixes partly `Broken pipe or closed connection` issue.

```
PHP Fatal error: Uncaught exception 'PhpAmqpLib\Exception\AMQPRuntimeException' with message 'Broken pipe or closed connection' in /some/path/vendor/php-amqplib/php-amqplib/PhpAmqpLib/Wire/IO/StreamIO.php:190
```

[back to index](../index.md)
