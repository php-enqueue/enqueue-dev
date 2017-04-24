# STOMP transport

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/stomp
```

## Create context

```php
<?php
use Enqueue\Stomp\StompConnectionFactory;

$connectionFactory = new StompConnectionFactory([
    'host' => '127.0.0.1',
    'port' => 61613,
    'login' => 'guest',
    'password' => 'guest',
    'vhost' => '/',
]);

$psrContext = $connectionFactory->createContext();
```

## Send message to topic 

```php
<?php
/** @var \Enqueue\Stomp\StompContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooTopic = $psrContext->createTopic('foo');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Stomp\StompContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooQueue = $psrContext->createQueue('foo');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Stomp\StompContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');

$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](index.md)