# Kafka transport

The transport uses [Kafka](https://kafka.apache.org/) streaming platform as a MQ broker.

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/rdkafka
```

## Create context

```php
<?php
use Enqueue\RdKafka\RdKafkaConnectionFactory;

// connects to localhost:9092
$connectionFactory = new RdKafkaConnectionFactory();

// same as above
$connectionFactory = new RdKafkaConnectionFactory('rdkafka://');

// same as above
$connectionFactory = new RdKafkaConnectionFactory([]);

// connect to Kafka broker at example.com:1000 plus custom options
$connectionFactory = new RdKafkaConnectionFactory([ 
    'global' => [
        'group.id' => uniqid('', true),
        'metadata.broker.list' => 'example.com:1000',
        'enable.auto.commit' => 'false',
    ],
    'topic' => [
        'auto.offset.reset' => 'beginning',
    ],
]);

$psrContext = $connectionFactory->createContext();
```

## Send message to topic 

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooTopic = $psrContext->createTopic('foo');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooQueue = $psrContext->createQueue('foo');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');

$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](index.md)