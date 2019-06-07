---
layout: default
title: Pheanstalk
parent: Transports
nav_order: 3
---
{% include support.md %}

# Beanstalk (Pheanstalk) transport

The transport uses [Beanstalkd](http://kr.github.io/beanstalkd/) job manager.
The transport uses [Pheanstalk](https://github.com/pda/pheanstalk) library internally.

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/pheanstalk
```


## Create context

```php
<?php
use Enqueue\Pheanstalk\PheanstalkConnectionFactory;

// connects to localhost:11300
$factory = new PheanstalkConnectionFactory();

// same as above
$factory = new PheanstalkConnectionFactory('beanstalk:');

// connects to example host and port 5555
$factory = new PheanstalkConnectionFactory('beanstalk://example:5555');

// same as above but configured by array
$factory = new PheanstalkConnectionFactory([
    'host' => 'example',
    'port' => 5555
]);

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('beanstalk:')->createContext();
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Pheanstalk\PheanstalkContext $context */

$fooTopic = $context->createTopic('aTopic');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\Pheanstalk\PheanstalkContext $context */

$fooQueue = $context->createQueue('aQueue');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Pheanstalk\PheanstalkContext $context */

$fooQueue = $context->createQueue('aQueue');
$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive(2000); // wait for 2 seconds

$message = $consumer->receiveNoWait(); // fetch message or return null immediately

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](../index.md)
