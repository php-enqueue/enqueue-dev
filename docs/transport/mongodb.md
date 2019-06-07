---
layout: default
title: MongoDB
parent: Transports
nav_order: 3
---
{% include support.md %}

# Enqueue Mongodb message queue transport

Allows to use [MongoDB](https://www.mongodb.com/) as a message queue broker.

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send priority message](#send-priority-message)
* [Send expiration message](#send-expiration-message)
* [Send delayed message](#send-delayed-message)
* [Consume message](#consume-message)
* [Subscription consumer](#subscription-consumer)

## Installation

```bash
$ composer require enqueue/mongodb
```

## Create context

```php
<?php
use Enqueue\Mongodb\MongodbConnectionFactory;

// connects to localhost
$connectionFactory = new MongodbConnectionFactory();

// same as above
$factory = new MongodbConnectionFactory('mongodb:');

// same as above
$factory = new MongodbConnectionFactory([]);

$factory = new MongodbConnectionFactory([
    'dsn' => 'mongodb://localhost:27017/db_name',
    'dbname' => 'enqueue',
    'collection_name' => 'enqueue',
    'polling_interval' => '1000',
]);

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('mongodb:')->createContext();
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooTopic */

$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooQueue */

$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send priority message

```php
<?php
/** @var \Enqueue\Mongodb\MongodbContext $context */

$fooQueue = $context->createQueue('foo');

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
/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooQueue */

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setTimeToLive(60000) // 60 sec
    //
    ->send($fooQueue, $message)
;
```

## Send delayed message

```php
<?php
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;

/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooQueue */

// make sure you run "composer require enqueue/amqp-tools".

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setDeliveryDelay(5000) // 5 sec

    ->send($fooQueue, $message)
;
````

## Consume message:

```php
<?php
/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooQueue */

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

/** @var \Enqueue\Mongodb\MongodbContext $context */
/** @var \Enqueue\Mongodb\MongodbDestination $fooQueue */
/** @var \Enqueue\Mongodb\MongodbDestination $barQueue */

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

[back to index](../index.md)
