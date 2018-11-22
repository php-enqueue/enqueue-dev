<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN 
$context = (new \Enqueue\ConnectionFactoryFactory())->create('stomp:')->createContext();
```

## Send message to topic 

```php
<?php
/** @var \Enqueue\Stomp\StompContext $context */

$message = $context->createMessage('Hello world!');

$fooTopic = $context->createTopic('foo');

$context->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Stomp\StompContext $context */

$message = $context->createMessage('Hello world!');

$fooQueue = $context->createQueue('foo');

$context->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Stomp\StompContext $context */

$fooQueue = $context->createQueue('foo');

$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](index.md)