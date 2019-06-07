---
layout: default
title: Redis
parent: Transports
nav_order: 3
---
{% include support.md %}

# Redis transport

The transport uses [Redis](https://redis.io/) as a message broker.
It creates a collection (a queue or topic) there. Pushes messages to the tail of the collection and pops from the head.
The transport works with [phpredis](https://github.com/phpredis/phpredis) php extension or [predis](https://github.com/nrk/predis) library.
Make sure you installed either of them

Features:
* Configure with DSN string
* Delay strategies out of the box
* Recovery&Redelivery support
* Expiration support
* Delaying support
* Interchangeable with other Queue Interop implementations
* Supports Subscription consumer

Parts:
* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send expiration message](#send-expiration-message)
* [Send delayed message](#send-delayed-message)
* [Consume message](#consume-message)
* [Delete queue (purge messages)](#delete-queue-purge-messages)
* [Delete topic (purge messages)](#delete-topic-purge-messages)
* [Connect Heroku Redis](#connect-heroku-redis)

## Installation

* With php redis extension:

```bash
$ apt-get install php-redis
$ composer require enqueue/redis
```

* With predis library:

```bash
$ composer require enqueue/redis predis/predis:^1
```

## Create context

* With php redis extension:

```php
<?php
use Enqueue\Redis\RedisConnectionFactory;

// connects to localhost
$factory = new RedisConnectionFactory();

// same as above
$factory = new RedisConnectionFactory('redis:');

// same as above
$factory = new RedisConnectionFactory([]);

// connect to Redis at example.com port 1000 using phpredis extension
$factory = new RedisConnectionFactory([
    'host' => 'example.com',
    'port' => 1000,
    'scheme_extensions' => ['phpredis'],
]);

// same as above but given as DSN string
$factory = new RedisConnectionFactory('redis+phpredis://example.com:1000');

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('redis:')->createContext();

// pass redis instance directly
$redis = new \Enqueue\Redis\PhpRedis([ /** redis connection options */ ]);
$redis->connect();

// Secure\TLS connection. Works only with predis library. Note second "S" in scheme.
$factory = new RedisConnectionFactory('rediss+predis://user:pass@host/0');

$factory = new RedisConnectionFactory($redis);
```

* With predis library:

```php
<?php
use Enqueue\Redis\RedisConnectionFactory;

$connectionFactory = new RedisConnectionFactory([
    'host' => 'localhost',
    'port' => 6379,
    'scheme_extensions' => ['predis'],
]);

$context = $connectionFactory->createContext();
```

* With predis and custom [options](https://github.com/nrk/predis/wiki/Client-Options):

It gives you more control over vendor specific features.

```php
<?php
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\PRedis;

$config = [
    'host' => 'localhost',
    'port' => 6379,
    'predis_options' => [
        'prefix'  => 'ns:'
    ]
];

$redis = new PRedis($config);

$factory = new RedisConnectionFactory($redis);
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */

$fooTopic = $context->createTopic('aTopic');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */

$fooQueue = $context->createQueue('aQueue');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send expiration message

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */
/** @var \Enqueue\Redis\RedisDestination $fooQueue */

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
/** @var \Enqueue\Redis\RedisContext $context */
/** @var \Enqueue\Redis\RedisDestination $fooQueue */

$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setDeliveryDelay(5000) // 5 sec

    ->send($fooQueue, $message)
;
````

## Consume message:

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */

$fooQueue = $context->createQueue('aQueue');
$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
//$consumer->reject($message);
```

## Delete queue (purge messages):

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */

$fooQueue = $context->createQueue('aQueue');

$context->deleteQueue($fooQueue);
```

## Delete topic (purge messages):

```php
<?php
/** @var \Enqueue\Redis\RedisContext $context */

$fooTopic = $context->createTopic('aTopic');

$context->deleteTopic($fooTopic);
```

## Connect Heroku Redis

[Heroku Redis](https://devcenter.heroku.com/articles/heroku-redis) describes how to setup Redis instance on Heroku.
To use it with Enqueue Redis you have to pass REDIS_URL to RedisConnectionFactory constructor.

```php
<?php

// REDIS_URL: redis://h:asdfqwer1234asdf@ec2-111-1-1-1.compute-1.amazonaws.com:111

$connection = new \Enqueue\Redis\RedisConnectionFactory(getenv('REDIS_URL'));
```

[back to index](../index.md)
