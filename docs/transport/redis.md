# Redis transport

The transport uses [Redis](https://redis.io/) as a message broker. 
It creates a collection (a queue or topic) there. Pushes messages to the tail of the collection and pops from the head.
The transport works with [phpredis](https://github.com/phpredis/phpredis) php extension or [predis](https://github.com/nrk/predis) library. 
Make sure you installed either of them 
 
**Limitations** It works only in auto ack mode hence If consumer crashes the message is lost.  

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
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
    'vendor' => 'phpredis',
]);

// same as above but given as DSN string
$factory = new RedisConnectionFactory('redis://example.com:1000?vendor=phpredis');

$psrContext = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN 
$psrContext = (new \Enqueue\ConnectionFactoryFactory())->create('redis:')->createContext();

// pass redis instance directly
$redis = new \Enqueue\Redis\PhpRedis([ /** redis connection options */ ]);
$redis->connect();

$factory = new RedisConnectionFactory($redis);
```

* With predis library:

```php
<?php
use Enqueue\Redis\RedisConnectionFactory;

$connectionFactory = new RedisConnectionFactory([
    'host' => 'localhost',
    'port' => 6379,
    'vendor' => 'predis',
]);

$psrContext = $connectionFactory->createContext();
```

* With custom redis instance:

It gives you more control over vendor specific features.

```php
<?php
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\PRedis;
 
$config = []; 
$options = [];

$redis = new PRedis(new \PRedis\Client($config, $options));

$factory = new RedisConnectionFactory(['vendor' => 'custom', 'redis' => $redis]);
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Redis\RedisContext $psrContext */

$fooTopic = $psrContext->createTopic('aTopic');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Redis\RedisContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Redis\RedisContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message
```

## Delete queue (purge messages):

```php
<?php
/** @var \Enqueue\Redis\RedisContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');

$psrContext->deleteQueue($fooQueue);
```

## Delete topic (purge messages):

```php
<?php
/** @var \Enqueue\Redis\RedisContext $psrContext */

$fooTopic = $psrContext->createTopic('aTopic');

$psrContext->deleteTopic($fooTopic);
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