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

// connects to localhost
$factory = new StompConnectionFactory();

// same as above
$factory = new StompConnectionFactory('stomp:');

// same as above
$factory = new StompConnectionFactory([]);

// connect to stomp broker at example.com port 1000 using 
$factory = new StompConnectionFactory([
    'host' => 'example.com',
    'port' => 1000,
    'login' => 'theLogin',
]);

// same as above but given as DSN string
$factory = new StompConnectionFactory('stomp://example.com:1000?login=theLogin');

$psrContext = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN 
$psrContext = (new \Enqueue\ConnectionFactoryFactory())->create('stomp:')->createContext();
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