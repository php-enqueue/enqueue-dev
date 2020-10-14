---
layout: default
title: DBAL
parent: Transports
nav_order: 3
---
{% include support.md %}

# Doctrine DBAL transport

The transport uses [Doctrine DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/) library and SQL like server as a broker.
It creates a table there. Pushes and pops messages to\from that table.

* [Installation](#installation)
* [Init database](#init-database)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send expiration message](#send-expiration-message)
* [Send delayed message](#send-delayed-message)
* [Consume message](#consume-message)
* [Subscription consumer](#subscription-consumer)

## Installation

```bash
$ composer require enqueue/dbal
```

## Create context

* With config (a connection is created internally):

```php
<?php
use Enqueue\Dbal\DbalConnectionFactory;

$factory = new DbalConnectionFactory('mysql://user:pass@localhost:3306/mqdev');

// connects to localhost
$factory = new DbalConnectionFactory('mysql:');

$context = $factory->createContext();
```

* With existing connection:

```php
<?php
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Doctrine\Persistence\ManagerRegistry;

/** @var ManagerRegistry $registry */

$factory = new ManagerRegistryConnectionFactory($registry, [
    'connection_name' => 'default',
]);

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('mysql:')->createContext();
```

## Init database

At first time you have to create a table where your message will live. There is a handy methods for this `createDataBaseTable` on the context.
Please pay attention to that the database has to be created manually.

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $context */

$context->createDataBaseTable();
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $context */

$fooTopic = $context->createTopic('aTopic');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $context */

$fooQueue = $context->createQueue('aQueue');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send expiration message

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */
/** @var \Enqueue\Dbal\DbalDestination $fooQueue */

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setTimeToLive(60000) // 60 sec
    //
    ->send($fooQueue, $message)
;
```

## Send delayed message

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */
/** @var \Enqueue\Dbal\DbalDestination $fooQueue */

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setDeliveryDelay(5000) // 5 sec
    //
    ->send($fooQueue, $message)
;
````

## Consume message:

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $context */

$fooQueue = $context->createQueue('aQueue');
$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
//$consumer->reject($message);
```

## Subscription consumer

```php
<?php
use Interop\Queue\Message;
use Interop\Queue\Consumer;

/** @var \Enqueue\Dbal\DbalContext $context */
/** @var \Enqueue\Dbal\DbalDestination $fooQueue */
/** @var \Enqueue\Dbal\DbalDestination $barQueue */

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
